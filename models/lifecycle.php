<?php

class Lifecycle extends AppModel
{
    public function getInitialInformationTable() {
        $res = $this->query("SHOW TABLES LIKE 'shop_schema'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE shop_schema (
                k VARCHAR(255) NOT NULL,
                v VARCHAR(255) NOT NULL
            )");
            $this->query("INSERT INTO shop_schema (k, v) VALUES ('rootAccountCreated', '0')");
            return false;
        }
        $res = $this->query("SELECT v FROM shop_schema WHERE k = 'rootAccountCreated'");
        if (count($res) == 0) {
            $this->query("INSERT INTO shop_schema (k, v) VALUES ('rootAccountCreated', '0')");
            return false;
        }
        if ($res[0]["v"] == "0") {
            return false;
        }
        return true;
    }

    public function getShops() {
        $res = $this->query("SHOW TABLES LIKE 'shops'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE shops (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(64) NOT NULL,
                description TEXT NOT NULL,
                creator int NOT NULL,
                owner varchar(24) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE (name)
            )");
            return array();
        }
        $res = $this->query("SELECT * FROM shops");
        // TODO: join auction count 
        return $res;
    }

    public function createShop($name, $description, $creator, $owner) {
        return $this->query("INSERT INTO shops (name, description, creator, owner) VALUES ('$name', '$description', '$creator', '$owner')");
        
    }

    public function getShop($id) {
        // TODO: join auctions
        $res = $this->query("SELECT name, description, owner, username FROM shops JOIN users ON creator=users.id WHERE shops.id = '$id'");
        if (count($res) == 0) {
            return false;
        }
        return $res[0];
    }
}