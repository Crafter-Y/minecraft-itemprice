<?php

class Lifecycle extends AppModel
{
    private function checkShopSchemaTable() {
        $res = $this->query("SHOW TABLES LIKE 'shop_schema'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE shop_schema (
                k VARCHAR(255) NOT NULL,
                v VARCHAR(255) NOT NULL
            )");
            $this->query("INSERT INTO shop_schema (k, v) VALUES ('rootAccountCreated', '0')");
            return false;
        }
        return true;
    }

    private function checkShopsTable() {
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
            return false;
        }
        return true;
    }

    private function checkAuctionsTable() {
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
            return false;
        }
        return true;
    }

    private function checkUsersTable() {
        $res = $this->query("SHOW TABLES LIKE 'users'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE users (
                id INT NOT NULL AUTO_INCREMENT,
                username VARCHAR(24) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(24) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE (username)
            )");
            return false;
        }
        return true;
    }

    private function checkTrendingTable() {
        $res = $this->query("SHOW TABLES LIKE 'trending'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE trending (
                item VARCHAR(64) NOT NULL,
                count INT NOT NULL,
                minPrice DOUBLE(20, 2) NOT NULL
            )");
        }
    }

    public function getInitialInformationTable() {
        $this->checkShopSchemaTable();
        $this->checkShopsTable();
        $this->checkAuctionsTable();
        $this->checkUsersTable();
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
        $this->checkShopsTable();
        $this->checkAuctionsTable();
        $res = $this->query("SELECT shops.name, shops.id, shops.description, shops.creator, shops.owner, COUNT(auctions.id) FROM shops LEFT JOIN auctions On shops.id = auctions.shopId GROUP BY shops.name");
        return $res;
    }

    public function createShop($name, $description, $creator, $owner) {
        $this->checkShopsTable();
        return $this->query("INSERT INTO shops (name, description, creator, owner) VALUES ('$name', '$description', '$creator', '$owner')");
    }

    public function getShop($id, $searchQuery) {
        $this->checkShopsTable();
        $this->checkUsersTable();
        $res = $this->query("SELECT name, description, owner, username FROM shops JOIN users ON creator=users.id WHERE shops.id = '$id'");
        if (count($res) == 0) {
            return false;
        }

        $res[0]["auctions"] = $this->getShopAuctions($id, $searchQuery);
        return $res[0];
    }

    private function cmp_item($key) {
        return function ($a, $b) use ($key) {
            return strcmp($a["item"], $b["item"]);
        };
    }

    public function getShopAuctions($shopId, $searchQuery) {
        $this->checkAuctionsTable();
        $res = $this->query("SELECT item, price, amount, id FROM auctions WHERE shopId = '$shopId'");
        
        if ($searchQuery) {
            $searchQuery = str_replace(" ", "_", $searchQuery);
            $newres = array();
            foreach ($res as $entry) {
                if (substr(strtolower($entry["item"]), 0, strlen($searchQuery)) == strtolower($searchQuery)) {
                    array_push($newres, $entry);
                }
            }
            usort($newres, $this->cmp_item('key_b'));
            return $newres;
        }
        usort($res, $this->cmp_item('key_b'));
        return $res;
    }

    public function createAuction($item, $price, $shopId, $creator, $amount) {
        $this->checkAuctionsTable();
        $res = $this->query("INSERT INTO auctions (item, price, shopId, creator, amount) VALUES ('$item', '$price', '$shopId', '$creator', '$amount')");

        $this->updateTrendingCache();

        return $res;
    }

    public function deleteAuction($id) {
        $this->checkAuctionsTable();
        return $this->query("DELETE FROM auctions WHERE id = '$id'");
    }

    public function hardReset() {
        // this function should be used only for testing purposes
        // this function should completely reset the database and delete all tables
        $this->query("DROP TABLE auctions");
        $this->query("DROP TABLE shops");
        $this->query("DROP TABLE users");
        $this->query("DROP TABLE shop_schema");
        $this->query("DROP TABLE trending");
    }

    public function isDefaultUserAllowedToViewMainController() {
        $this->checkShopSchemaTable();
        $res = $this->query("SELECT `v` FROM `shop_schema` WHERE `k` = 'defaultUserAccess'");
        if (count($res) == 0) {
            $this->query("INSERT INTO `shop_schema` (`k`, `v`) VALUES ('defaultUserAccess', '0')");
            return false;
        }
        if ($res[0]["v"] == "0") {
            return false;
        }
        return true;
    }

    public function setDefaultUserAccess($value) {
        $value = $value ? "1" : "0";
        $this->query("UPDATE shop_schema SET v = '$value' WHERE k = 'defaultUserAccess'");
    }

    private function updateTrendingCache() {
        $this->checkTrendingTable();
        $this->query("TRUNCATE TABLE trending");
        $res = $this->query("SELECT item, COUNT(item) AS count, MIN(price / amount) AS minPrice FROM `auctions` GROUP BY item");
        foreach ($res as $entry) {
            $this->query("INSERT INTO trending (item, count, minPrice) VALUES ('$entry[item]', '$entry[count]', '$entry[minPrice]')");
        }
    }

    private function cmp_count($key) {
        return function ($a, $b) use ($key) {
            return $a["count"] < $b["count"];
        };
    }

    public function getTrending($sortation, $searchQuery) {
        $this->checkTrendingTable();
        $res = $this->query("SELECT * FROM trending");
        if ($sortation) {
            if ($sortation == "trending") {
                usort($res, $this->cmp_count('key_b'));
            } else if ($sortation == "alphanumerical") {
                usort($res, $this->cmp_item('key_b'));
            }
        } else {
            usort($res, $this->cmp_count('key_b'));
        }

        if ($searchQuery) {
            $searchQuery = str_replace(" ", "_", $searchQuery);
            $newres = array();
            foreach ($res as $entry) {
                if (substr(strtolower($entry["item"]), 0, strlen($searchQuery)) == strtolower($searchQuery)) {
                    array_push($newres, $entry);
                }
            }
            return $newres;
        }
        
        return $res;
    }

    public function isAdminAllowedToEditShop() {
        $this->checkShopSchemaTable();
        $res = $this->query("SELECT `v` FROM `shop_schema` WHERE `k` = 'isAdminAllowedToEditShop'");
        if (count($res) == 0) {
            $this->query("INSERT INTO `shop_schema` (`k`, `v`) VALUES ('isAdminAllowedToEditShop', '0')");
            return false;
        }
        if ($res[0]["v"] == "0") {
            return false;
        }
        return true;
    }

    public function configureShop($shopId, $name, $description, $owner) {
        $this->checkShopsTable();
        $this->query("UPDATE shops SET name = '$name', description = '$description', owner = '$owner' WHERE id = '$shopId'");
    }

    public function setAdminAllowedToEditShop($state) {
        $state = $state ? "1" : "0";
        $this->checkShopSchemaTable();
        $this->query("UPDATE shop_schema SET v = '$state' WHERE k = 'isAdminAllowedToEditShop'");
    }

    public function deleteShop($shopId){
        $this->checkShopsTable();
        $this->query("DELETE FROM shops WHERE id = '$shopId'");
        $this->query("DELETE FROM auctions WHERE shopId = '$shopId'");
        $this->updateTrendingCache();
    }

    private function cmp_pricePerPc($key) {
        return function ($a, $b) use ($key) {
            return $a["pricePerPc"] > $b["pricePerPc"];
        };
    }

    public function getView($itemName) {
        $returner = array("ok" => true);

        $res = $this->query("SELECT auctions.price, auctions.amount, auctions.shopId, shops.name, shops.description, shops.owner FROM auctions JOIN shops ON auctions.shopId = shops.id WHERE item = '$itemName'");
        if (count($res) == 0) {
            $returner["ok"] = false;
            return $returner;
        }

        $sumPerPc = 0;
        $sumPerStack = 0;
        foreach($res as $key => $row) {
            $res[$key]["pricePerPc"] = $row["price"] / $row["amount"];
            $res[$key]["pricePerStack"] = $row["price"] / $row["amount"] * 64;
            $sumPerPc += $res[$key]["pricePerPc"];
            $sumPerStack += $res[$key]["pricePerStack"];
        }
        usort($res, $this->cmp_pricePerPc('key_b'));
        $returner["data"] = $res;
        $returner["sumPerPc"] = $sumPerPc / count($res);
        $returner["sumPerStack"] = $sumPerStack / count($res);

        return $returner;
    }
}