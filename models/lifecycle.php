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
        $res = $this->query("SELECT shops.name, shops.id, shops.description, shops.creator, shops.owner, COUNT(auctions.id) FROM shops LEFT JOIN auctions On shops.id = auctions.shopId GROUP BY shops.name");
        return $res;
    }

    public function createShop($name, $description, $creator, $owner) {
        return $this->query("INSERT INTO shops (name, description, creator, owner) VALUES ('$name', '$description', '$creator', '$owner')");
        
    }

    public function getShop($id, $searchQuery) {
        $res = $this->query("SELECT name, description, owner, username FROM shops JOIN users ON creator=users.id WHERE shops.id = '$id'");
        if (count($res) == 0) {
            return false;
        }

        $res[0]["auctions"] = $this->getShopAuctions($id, $searchQuery);
        return $res[0];
    }

    private function cmp($key) {
        return function ($a, $b) use ($key) {
            return strcmp($a["item"], $b["item"]);
        };
    }

    public function getShopAuctions($shopId, $searchQuery) {
        $res = $this->query("SHOW TABLES LIKE 'auctions'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE auctions (
                id INT NOT NULL AUTO_INCREMENT,
                item VARCHAR(64) NOT NULL,
                price DOUBLE(20, 2) NOT NULL,
                shopId INT NOT NULL,
                creator INT NOT NULL,
                amount INT NOT NULL,
                PRIMARY KEY (id)
            )");
            return array();
        }
        $res = $this->query("SELECT item, price, amount, id FROM auctions WHERE shopId = '$shopId'");
        
        if ($searchQuery) {
            
            $newres = array();
            foreach ($res as $entry) {
                if (substr(strtolower($entry["item"]), 0, strlen($searchQuery)) === strtolower($searchQuery)) {
                    array_push($newres, $entry);
                }
            }
            usort($newres, $this->cmp('key_b'));
            return $newres;
        }
        usort($res, $this->cmp('key_b'));
        return $res;
    }

    public function createAuction($item, $price, $shopId, $creator, $amount) {
        return $this->query("INSERT INTO auctions (item, price, shopId, creator, amount) VALUES ('$item', '$price', '$shopId', '$creator', '$amount')");
    }

    public function deleteAuction($id) {
        return $this->query("DELETE FROM auctions WHERE id = '$id'");
    }
}