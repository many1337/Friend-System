<?php
namespace SlashMC;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Friends extends PluginBase implements Listener
{
    const SlashMC = "SlashMC";

    public $mysql;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getLogger()->info("\n------------------------------------\nBitte Warte...\nLade Resourcen....\nPlugin Erkannt...\nWir sind Online Netzwerk status ok!\n------------------------------------");
        $this->getLogger()->info(TextFormat::BLUE . " Plugin Fixed by " . TextFormat::RED . self::SlashMC);

        @mkdir($this->getDataFolder());

        $config = new Config($this->getDataFolder() . "config.yml");

        if (!$config->exists("MySQL")) {
            $config->setNested("MySQL.Address", "127.0.0.1");
            $config->setNested("MySQL.User", "root");
            $config->setNested("MySQL.Password", "1234");
            $config->setNested("MySQL.Database", "Freunde");
            $config->save();

            $this->getLogger()->info("BITTE IN DER CONFIG DIE MYSQL DATEN AUSFÜHREN UND DEN SERVER NEU STARTEN!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

        $data = $config->get("MySQL");

        $this->mysql = new MySQL($data["Address"], $data["User"], $data["Password"], $data["Database"]);

    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();

        $exists = false;
        $sql = $this->mysql->query("SELECT * FROM Freunde WHERE Name='$name'");
        $data = mysqli_fetch_assoc($sql);

        if ($data) {
            $exists = true;
        }


        if (!$exists) {
            $sql = "INSERT INTO Freunde 
            (Name, Freunde, Anfragen) 
            VALUES 
            ('$name', '-', '-')";

            if ($this->mysql->query($sql) !== TRUE) {
                echo "Error: " . $sql . " || " . $this->mysql->error;
            }
        }


        $this->friendsOnline($player);
    }

    public function friendsOnline(Player $player)
    {
        $name = $player->getName();
        $freundesPrefix = TextFormat::ESCAPE . "7[" . TextFormat::ESCAPE . "4Freunde" . TextFormat::ESCAPE . "7] " . TextFormat::ESCAPE . "f";

        $freunde = $this->mysql->getFreunde($name);

        foreach ($freunde as $friend) {
            if ($friend != "-") {
                $target = $this->getServer()->getPlayerExact($friend);
                if ($target != null) {
                    $target->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $name . " " . TextFormat::ESCAPE . "aist nun Online!");
                }
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args): bool {
        $name = $sender->getName();
        $freundesPrefix = TextFormat::ESCAPE . "7[" . TextFormat::ESCAPE . "4Freunde" . TextFormat::ESCAPE . "7] " . TextFormat::ESCAPE . "f";


        if (strtolower($cmd->getName()) === "friend") {
            if ($sender instanceof Player) {
                $freunde = $this->mysql->getFreunde($name);
                $anfragen = $this->mysql->getAnfragen($name);

                if (!empty($args[0])) {
                    if (strtolower($args[0]) == "invite" || strtolower($args[0]) == "add") {
                        if (!empty($args[1])) {
                            $targetname = $args[1];
                            $targetinvites = $this->mysql->getAnfragen($targetname);

                            if (!in_array($targetname, $freunde)) {
                                if (!in_array($targetname, $targetinvites)) {
                                    $target = $this->getServer()->getPlayerExact($targetname);
                                    if ($target != null) {
                                        $target->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $name . " " . TextFormat::ESCAPE . "ahat dir eine Freundesanfrage gesendet!");
                                        $target->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6/friend accept " . $name . " " . TextFormat::ESCAPE . "azum " . TextFormat::ESCAPE . "aAnnehmen");
                                        $target->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6/friend deny " . $name . " " . TextFormat::ESCAPE . "azum " . TextFormat::ESCAPE . "cAblehnen");

                                        $targetinvites[] = $name;

                                        $this->mysql->setAnfragen($targetname, $targetinvites);

                                        $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "aDeine Einladung wurde abgeschickt!");
                                    } else {
                                        $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "cSpieler wurde nicht Gefunden!");
                                    }
                                } else {
                                    $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "cDu hast " . TextFormat::ESCAPE . "6" . $targetname . " " . TextFormat::ESCAPE . "cBereits eine Anfrage gesendet!");
                                }
                            } else {
                                $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $targetname . " " . TextFormat::ESCAPE . "cist Bereits in deiner Freundesliste!");
                            }
                        } else {
                            $sender->sendMessage($freundesPrefix . "/friend invite <player>");
                        }
                    } elseif (strtolower($args[0]) == "del" || strtolower($args[0]) == "delete") {
                        if (!empty($args[1])) {
                            if (in_array($args[1], $freunde)) {
                                $targetname = $args[1];
                                $targetfriends = $this->mysql->getFreunde($targetname);

                                $target = $this->getServer()->getPlayerExact($targetname);

                                unset($freunde[array_search($targetname, $freunde)]);
                                unset($targetfriends[array_search($name, $targetfriends)]);

                                $this->mysql->setFreunde($name, $freunde);
                                $this->mysql->setFreunde($targetname, $targetfriends);

                                if ($target != null) {
                                    $target->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $name . " " . TextFormat::ESCAPE . "ahat die Freundschaft mit dir Beendet!");
                                }
                                $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "aDu hast die Freundschaft mit " . TextFormat::ESCAPE . "6" . $targetname . " " . TextFormat::ESCAPE . "aBeendet!");
                            } else {
                                $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $args[1] . " " . TextFormat::ESCAPE . "cist nicht in deiner Freundesliste!");
                            }
                        } else {
                            $sender->sendMessage($freundesPrefix . "/friend del <player>");
                        }
                    } elseif (strtolower($args[0]) == "accept") {
                        if (!empty($args[1])) {
                            if (!in_array($args[1], $freunde)) {
                                $targetname = $args[1];
                                $targetinvites = $this->mysql->getAnfragen($targetname);
                                $targetfriends = $this->mysql->getFreunde($targetname);

                                $target = $this->getServer()->getPlayerExact($targetname);

                                if (in_array($targetname, $anfragen)) {
                                    unset($anfragen[array_search($targetname, $anfragen)]);
                                    $freunde[] = $targetname;
                                    $this->mysql->setFreunde($name, $freunde);
                                    $this->mysql->setAnfragen($name, $anfragen);

                                    $targetfriends[] = $name;
                                    $this->mysql->setFreunde($targetname, $targetfriends);

                                    if ($target != null) {
                                        $target->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $name . " " . TextFormat::ESCAPE . "ahat deine Freundesanfrage " . TextFormat::ESCAPE . "aAngenommen!");
                                    }
                                    $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "aDu hast die Anfrage von " . TextFormat::ESCAPE . "6" . $targetname . " " . TextFormat::ESCAPE . "aAngenommen!");
                                } else {
                                    $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "cDieser Spieler hat dir keine Freundesanfrage gesendet!");
                                }
                            } else {
                                $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $args[1] . " " . TextFormat::ESCAPE . "cist Bereits in deiner Freundesliste!");
                            }
                        } else {
                            $sender->sendMessage($freundesPrefix . "/friend accept <player>");
                        }
                    } elseif (strtolower($args[0]) == "deny") {
                        if (!empty($args[1])) {
                            if (!in_array($args[1], $freunde)) {
                                $targetname = $args[1];
                                $targetinvites = $this->mysql->getAnfragen($targetname);
                                $targetfriends = $this->mysql->getFreunde($targetname);

                                $target = $this->getServer()->getPlayerExact($targetname);

                                if (in_array($targetname, $anfragen)) {
                                    unset($anfragen[array_search($targetname, $anfragen)]);
                                    $this->mysql->setAnfragen($name, $anfragen);

                                    if ($target != null) {
                                        $target->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $name . " " . TextFormat::ESCAPE . "ahat deine Freundesanfrage " . TextFormat::ESCAPE . "cAbgelehnt!");
                                    }
                                    $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "aDu hast die Anfrage von " . TextFormat::ESCAPE . "6" . $targetname . " " . TextFormat::ESCAPE . "cAbgelehnt!");
                                } else {
                                    $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "cDieser Spieler hat dir keine Freundesanfrage gesendet!");
                                }
                            } else {
                                $sender->sendMessage($freundesPrefix . TextFormat::ESCAPE . "6" . $args[1] . " " . TextFormat::ESCAPE . "cist Bereits in deiner Freundesliste!");
                            }
                        } else {
                            $sender->sendMessage($freundesPrefix . "/friend deny <player>");
                        }
                    } elseif (strtolower($args[0]) == "list") {
                        $sender->sendMessage(TextFormat::ESCAPE . "7==]" . TextFormat::ESCAPE . "4Freunde" . TextFormat::ESCAPE . "7[==");

                        foreach ($freunde as $friend) {
                            if ($friend != "steve") {
                                if ($this->getServer()->getPlayerExact($friend) != null) {
                                    $sender->sendMessage(TextFormat::ESCAPE . "7- " . TextFormat::ESCAPE . "f" . $friend . " " . TextFormat::ESCAPE . "7[" . TextFormat::ESCAPE . "aOnline" . TextFormat::ESCAPE . "7]");
                                } else {
                                    $sender->sendMessage(TextFormat::ESCAPE . "7- " . TextFormat::ESCAPE . "f" . $friend . " " . TextFormat::ESCAPE . "7[" . TextFormat::ESCAPE . "cOffline" . TextFormat::ESCAPE . "7]");
                                }
                            }
                        }
                        $sender->sendMessage(" ");
                    }
                } else {
                    $sender->sendMessage($freundesPrefix . "\n------------\n/friend invite \n/friend del \n/firend accept \n/friend deny \n/friend list\n------------");
                }
            } else {
                $sender->sendMessage(TextFormat::ESCAPE . "Die Konsole hat keine Freunde xD");
            }
	return true;
        }
    }

}