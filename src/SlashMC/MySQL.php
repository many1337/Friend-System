<?php

namespace SlashMC;

class MySQL
{

    public $isConnected = false;
    public $mysql;

    public function __construct($address, $user, $pass, $db)
    {
        // Create connection
        $this->mysql = @mysqli_connect($address, $user, $pass, $db);

        // Check connection
        if ($this->mysql->connect_error) {
            die("Verbindungsfehler: " . $this->mysql->connect_error);
        } else {
            $this->isConnected = true;
        }

        if ($this->isConnected) {
            $this->query("
                CREATE TABLE IF NOT EXISTS Freunde (
                Name VARCHAR(50) PRIMARY KEY,
                Freunde VARCHAR(50),
                Anfragen VARCHAR(50)
                )
            ");

            $count = mysqli_num_rows(mysqli_query($this->mysql, "SELECT * FROM Freunde"));
            if ($count < 1) {
                $sql = "INSERT INTO Freunde 
                        (Name, Freunde, Anfragen) 
                        VALUES 
                        ('-', '-', '-')";

                if ($this->mysql->query($sql) !== TRUE) {
                    echo "Error: " . $sql . " || " . $this->mysql->error;
                }
            }
        }
    }

    public function setAnfragen($name, $array)
    {
        if (count($array) == 0) {
            $anfragen = "-";
        } else {
            $anfragen = implode(",", $array);
        }

        $sql = "UPDATE Freunde SET Anfragen='$anfragen' WHERE Name='$name'";
        $this->mysql->query($sql);
    }

    public function getAnfragen($name)
    {
        $sql = $this->query("SELECT * FROM Freunde WHERE Name='$name'");
        $data = mysqli_fetch_assoc($sql);
        $anfragen = $data["Anfragen"];

        if ($anfragen == "-") {
            $anfragen = [];
        } else {
            $anfragen = explode(",", $anfragen);
        }

        return $anfragen;
    }

    public function setFreunde($name, $array)
    {
        if (count($array) == 0) {
            $freunde = "-";
        } else {
            $freunde = implode(",", $array);
        }

        $sql = "UPDATE Freunde SET Freunde='$freunde' WHERE Name='$name'";
        $this->mysql->query($sql);
    }

    public function getFreunde($name)
    {
        $sql = $this->query("SELECT * FROM Freunde WHERE Name='$name'");
        $data = mysqli_fetch_assoc($sql);
        $freunde = $data["Freunde"];

        if ($freunde == "-") {
            $freunde = [];
        } else {
            $freunde = explode(",", $freunde);
        }

        return $freunde;
    }

    public function query($sql)
    {
        //$sql = "UPDATE Kunden SET Passwort='$passwort_hash', Nickname='$nickname' WHERE EMail='$email'";

        $result = mysqli_query($this->mysql, $sql);

        if (mysqli_error($this->mysql)) {
            echo "Error: " . $sql . " || " . $this->mysql->error . "\n";
        }

        return $result;

    }

}