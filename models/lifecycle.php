<?php

class Lifecycle extends AppModel
{
    private function checkShopSchemaTable()
    {
        $res = $this->query("SHOW TABLES LIKE 'shop_schema'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE shop_schema (
                k VARCHAR(255) NOT NULL,
                v VARCHAR(255) NOT NULL
            )");
            $this->query(
                "INSERT INTO shop_schema (k, v) VALUES ('rootAccountCreated', '0')",
            );
            return false;
        }
        return true;
    }

    private function checkShopsTable()
    {
        $res = $this->query("SHOW TABLES LIKE 'shops'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE shops (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(64) NOT NULL,
                description TEXT NOT NULL,
                creator int NOT NULL,
                owner varchar(24) NOT NULL,
                defaultNotMaintained BOOLEAN DEFAULT false,
                defaultReliable BOOLEAN DEFAULT false,
                defaultMostlyAvailable BOOLEAN DEFAULT false,
                isLimited BOOLEAN DEFAULT false,
                PRIMARY KEY (id),
                UNIQUE (name)
            )");
            return false;
        }
        return true;
    }

    private function checkAuctionsTable()
    {
        $res = $this->query("SHOW TABLES LIKE 'auctions'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE auctions (
                id INT NOT NULL AUTO_INCREMENT,
                item VARCHAR(64) NOT NULL,
                identifier VARCHAR(64) DEFAULT NULL,
                price DOUBLE(20, 2) NOT NULL,
                shopId INT NOT NULL,
                creator INT NOT NULL,
                amount INT NOT NULL,
                timeCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
                notMaintained BOOLEAN DEFAULT false,
                reliable BOOLEAN DEFAULT false,
                mostlyAvailable BOOLEAN DEFAULT false,
                unique_md5 CHAR(32) AS 
                    (
                        MD5(
                            CONCAT_WS('X', 
                                timeCreated,
                                ifnull(identifier, 0)
                            )
                        )
                    ),
                PRIMARY KEY (id),
                UNIQUE (unique_md5)
            )");
            return false;
        }
        return true;
    }

    private function checkUsersTable()
    {
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

    private function checkTrendingTable()
    {
        $res = $this->query("SHOW TABLES LIKE 'trending'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE trending (
                item VARCHAR(64) NOT NULL,
                count INT NOT NULL,
                minPrice DOUBLE(20, 2) NOT NULL,
                UNIQUE (item)
            )");
        }
    }

    private function checkTrendingIncludingLimitedTable()
    {
        $res = $this->query("SHOW TABLES LIKE 'trendingIncludingLimited'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE trendingIncludingLimited (
                item VARCHAR(64) NOT NULL,
                count INT NOT NULL,
                minPrice DOUBLE(20, 2) NOT NULL,
                UNIQUE (item)
            )");
        }
    }

    private function checkTokensTable()
    {
        $res = $this->query("SHOW TABLES LIKE 'tokens'");
        if (count($res) == 0) {
            $this->query("CREATE TABLE tokens (
                token VARCHAR(24) NOT NULL,
                shop INT NOT NULL,
                creator INT NOT NULL,
                PRIMARY KEY (token)
            )");
        }
    }

    public function getInitialInformationTable()
    {
        $this->checkShopSchemaTable();
        $this->checkShopsTable();
        $this->checkAuctionsTable();
        $this->checkUsersTable();
        $res = $this->query(
            "SELECT v FROM shop_schema WHERE k = 'rootAccountCreated'",
        );
        if (count($res) == 0) {
            $this->query(
                "INSERT INTO shop_schema (k, v) VALUES ('rootAccountCreated', '0')",
            );
            return false;
        }
        if ($res[0]["v"] == "0") {
            return false;
        }
        return true;
    }

    public function getShops()
    {
        $this->checkShopsTable();
        $this->checkAuctionsTable();
        $res = $this->query(
            "SELECT 
                shops.name, 
                shops.id, 
                shops.description, 
                shops.creator, 
                shops.owner, 
                shops.defaultNotMaintained, 
                shops.defaultReliable, 
                shops.defaultMostlyAvailable, 
                shops.isLimited, 
                COUNT(auctions.id) 
            FROM shops 
            LEFT JOIN auctions On 
                shops.id = auctions.shopId 
            GROUP BY shops.name",
        );
        return $res;
    }

    public function createShop(
        $name,
        $description,
        $creator,
        $owner,
        $defaultNotMaintained,
        $defaultReliable,
        $defaultMostlyAvailable,
        $isLimited
    ) {
        $this->checkShopsTable();
        $this->query(
            "INSERT INTO shops (name, description, creator, owner, defaultNotMaintained, defaultReliable, defaultMostlyAvailable, isLimited) VALUES ('$name', '$description', '$creator', '$owner', '$defaultNotMaintained', '$defaultReliable', '$defaultMostlyAvailable', '$isLimited')",
        );
    }

    public function getShop($id, $searchQuery)
    {
        $this->checkShopsTable();
        $this->checkUsersTable();
        $res = $this->query(
            "SELECT name, description, owner, username, defaultNotMaintained, defaultReliable, defaultMostlyAvailable, isLimited FROM shops JOIN users ON creator=users.id WHERE shops.id = '$id'",
        );
        if (count($res) == 0) {
            return false;
        }

        $res[0]["auctions"] = $this->getShopAuctions($id, $searchQuery);
        return $res[0];
    }

    private function cmp_item($key)
    {
        return function ($a, $b) use ($key) {
            return strcmp($a["item"], $b["item"]);
        };
    }

    public function getShopAuctions($shopId, $searchQuery)
    {
        $this->checkAuctionsTable();
        $res = $this->query(
            "SELECT item, price, amount, id, notMaintained, reliable, mostlyAvailable 
            FROM auctions 
            WHERE shopId = '$shopId'",
        );

        if ($searchQuery) {
            $searchQuery = str_replace(" ", "_", $searchQuery);
            $newres = [];
            foreach ($res as $entry) {
                if (
                    substr(
                        strtolower($entry["item"]),
                        0,
                        strlen($searchQuery),
                    ) == strtolower($searchQuery)
                ) {
                    array_push($newres, $entry);
                }
            }
            usort($newres, $this->cmp_item("key_b"));
            return $newres;
        }
        usort($res, $this->cmp_item("key_b"));
        return $res;
    }

    public function createAuction($item, $price, $shopId, $creator, $amount)
    {
        $this->checkAuctionsTable();
        $res = $this->query(
            "INSERT INTO auctions (item, price, shopId, creator, amount, notMaintained, reliable, mostlyAvailable) VALUES 
            (
                '$item', 
                '$price', 
                '$shopId', 
                '$creator', 
                '$amount',
                (SELECT defaultNotMaintained FROM shops WHERE id = '$shopId'),
                (SELECT defaultReliable FROM shops WHERE id = '$shopId'),
                (SELECT defaultMostlyAvailable FROM shops WHERE id = '$shopId')
            )",
        );

        $this->updateTrendingCache();

        return $res;
    }

    public function deleteAuction($id)
    {
        $this->checkAuctionsTable();
        $res = $this->query("DELETE FROM auctions WHERE id = '$id'");
        $this->updateTrendingCache();
        return $res;
    }

    public function hardReset()
    {
        // this function should be used only for testing purposes
        // this function should completely reset the database and delete all tables
        $this->query("DROP TABLE auctions");
        $this->query("DROP TABLE shops");
        $this->query("DROP TABLE users");
        $this->query("DROP TABLE shop_schema");
        $this->query("DROP TABLE trending");
        $this->query("DROP TABLE trendingincludinglimited");
        $this->query("DROP TABLE tokens");
    }

    public function isDefaultUserAllowedToViewMainController()
    {
        $this->checkShopSchemaTable();
        $res = $this->query(
            "SELECT `v` FROM `shop_schema` WHERE `k` = 'defaultUserAccess'",
        );
        if (count($res) == 0) {
            $this->query(
                "INSERT INTO `shop_schema` (`k`, `v`) VALUES ('defaultUserAccess', '0')",
            );
            return false;
        }
        if ($res[0]["v"] == "0") {
            return false;
        }
        return true;
    }

    public function setDefaultUserAccess($value)
    {
        $value = $value ? "1" : "0";
        $this->query(
            "UPDATE shop_schema SET v = '$value' WHERE k = 'defaultUserAccess'",
        );
    }

    private function updateTrendingCache()
    {
        $this->checkTrendingTable();
        $this->checkTrendingIncludingLimitedTable();
        $this->query("TRUNCATE TABLE trending");
        $this->query("TRUNCATE TABLE trendingIncludingLimited");
        $res = $this->query(
            "SELECT 
                item, 
                COUNT(item) AS count, 
                MIN(price / amount) AS minPrice 
            FROM `auctions` 
            INNER JOIN `shops` ON auctions.shopId = shops.id 
            WHERE shops.isLimited != 1 
            GROUP BY item",
        );
        foreach ($res as $entry) {
            $this->query(
                "INSERT IGNORE INTO trending (item, count, minPrice) VALUES ('$entry[item]', '$entry[count]', '$entry[minPrice]')",
            );
        }

        $res = $this->query(
            "SELECT 
                item, 
                COUNT(item) AS count, 
                MIN(price / amount) AS minPrice 
            FROM `auctions` 
            GROUP BY item",
        );
        foreach ($res as $entry) {
            $this->query(
                "INSERT IGNORE INTO trendingIncludingLimited (item, count, minPrice) VALUES ('$entry[item]', '$entry[count]', '$entry[minPrice]')",
            );
        }
    }

    private function cmp_count($key)
    {
        return function ($a, $b) use ($key) {
            return $a["count"] < $b["count"];
        };
    }

    public function getTrending($sortation, $searchQuery, $includeLimited)
    {
        if ($includeLimited) {
            $this->checkTrendingIncludingLimitedTable();
            $res = $this->query(
                "SELECT item, count, minPrice FROM trendingIncludingLimited",
            );
        } else {
            $this->checkTrendingTable();
            $res = $this->query("SELECT item, count, minPrice FROM trending");
        }
        if ($sortation) {
            if ($sortation == "trending") {
                usort($res, $this->cmp_count("key_b"));
            } elseif ($sortation == "alphanumerical") {
                usort($res, $this->cmp_item("key_b"));
            }
        } else {
            usort($res, $this->cmp_count("key_b"));
        }

        if ($searchQuery) {
            $searchQuery = str_replace(" ", "_", $searchQuery);
            $newres = [];
            foreach ($res as $entry) {
                if (
                    substr(
                        strtolower($entry["item"]),
                        0,
                        strlen($searchQuery),
                    ) == strtolower($searchQuery)
                ) {
                    array_push($newres, $entry);
                }
            }
            return $newres;
        }

        return $res;
    }

    public function isAdminAllowedToEditShop()
    {
        $this->checkShopSchemaTable();
        $res = $this->query(
            "SELECT `v` FROM `shop_schema` WHERE `k` = 'isAdminAllowedToEditShop'",
        );
        if (count($res) == 0) {
            $this->query(
                "INSERT INTO `shop_schema` (`k`, `v`) VALUES ('isAdminAllowedToEditShop', '0')",
            );
            return false;
        }
        if ($res[0]["v"] == "0") {
            return false;
        }
        return true;
    }

    public function configureShop(
        $shopId,
        $name,
        $description,
        $owner,
        $notMaintained,
        $reliable,
        $mostlyAvailable,
        $limited
    ) {
        $this->checkShopsTable();
        $this->query(
            "UPDATE shops SET 
                name = '$name', 
                description = '$description', 
                owner = '$owner', 
                defaultNotMaintained = '$notMaintained', 
                defaultReliable = '$reliable',
                defaultMostlyAvailable = '$mostlyAvailable',
                isLimited = '$limited'
            
            WHERE id = '$shopId'",
        );
        $this->updateTrendingCache();
    }

    public function setAdminAllowedToEditShop($state)
    {
        $state = $state ? "1" : "0";
        $this->checkShopSchemaTable();
        $this->query(
            "UPDATE shop_schema SET v = '$state' WHERE k = 'isAdminAllowedToEditShop'",
        );
    }

    public function deleteShop($shopId)
    {
        $this->checkShopsTable();
        $this->query("DELETE FROM shops WHERE id = '$shopId'");
        $this->query("DELETE FROM auctions WHERE shopId = '$shopId'");
        $this->updateTrendingCache();
    }

    private function cmp_pricePerPc($key)
    {
        return function ($a, $b) use ($key) {
            return $a["pricePerPc"] > $b["pricePerPc"];
        };
    }

    public function getView($itemName, $period)
    {
        $returner = ["ok" => true];

        $res = $this->query(
            "SELECT 
                auctions.price, 
                auctions.amount, 
                auctions.shopId, 
                auctions.notMaintained, 
                auctions.reliable, 
                auctions.mostlyAvailable, 
                auctions.timeCreated, 

                shops.name, 
                shops.description, 
                shops.owner, 
                shops.isLimited
            FROM auctions 
            JOIN shops ON auctions.shopId = shops.id 
            WHERE item = '$itemName'",
        );
        if (count($res) == 0) {
            $returner["ok"] = false;
            return $returner;
        }

        $sumPerPc = 0;
        $auctionSumPerPc = 0;
        $auctionCount = 0;
        $sumPerStack = 0;
        $lowestAuction = null;
        $auctions = [];
        $limitedAuctions = [];
        foreach ($res as $key => $row) {
            $res[$key]["pricePerPc"] = $row["price"] / $row["amount"];
            $res[$key]["pricePerStack"] = ($row["price"] / $row["amount"]) * 64;
            $res[$key]["timeCreated"] =
                DateTime::createFromFormat(
                    "Y-m-d H:i:s",
                    $row["timeCreated"],
                )->getTimestamp() * 1000;
            if (!$row["isLimited"]) {
                $sumPerPc += $res[$key]["pricePerPc"];
                $sumPerStack += $res[$key]["pricePerStack"];
                array_push($auctions, $res[$key]);
            } else {
                $auctionSumPerPc += $res[$key]["pricePerPc"];
                $auctionCount++;
                if (
                    !$lowestAuction ||
                    $lowestAuction["pricePerPc"] > $res[$key]["pricePerPc"]
                ) {
                    $lowestAuction = $res[$key];
                }
                if (!isset($limitedAuctions[$row["name"]])) {
                    $limitedAuctions[$row["name"]] = [];
                }
                array_push($limitedAuctions[$row["name"]], $res[$key]);
            }
        }
        usort($auctions, $this->cmp_pricePerPc("key_b"));

        foreach ($limitedAuctions as $shop => $theseAuctions) {
            usort($theseAuctions, function ($a, $b) {
                return $a["timeCreated"] < $b["timeCreated"];
            });
            $limitedAuctions[$shop] = $theseAuctions;
        }

        $returner["auctions"] = $auctions;
        $returner["limitedAuctions"] = $limitedAuctions;

        if (count($auctions)) {
            $returner["hasAuctions"] = true;
            $returner["sumPerPc"] = $sumPerPc / count($auctions);
            $returner["sumPerStack"] = $sumPerStack / count($auctions);
        } else {
            $returner["hasAuctions"] = false;
        }

        if (count($limitedAuctions)) {
            $returner["hasLimitedAuctions"] = true;
            if ($period == "alltime") {
                $returner["auctionsInTimePeroid"] = true;
                return $returner;
            }
            $now = date_create()->getTimestamp() * 1000;
            if ($period == "lastyear") {
                $now = $now - 31557600000;
            } elseif ($period == "lastmonth") {
                $now = $now - 2629800000;
            } elseif ($period == "lastday") {
                $now = $now - 86400000;
            }

            $newLimitedAuctions = [];
            $empty = true;
            foreach ($limitedAuctions as $shop => $theseAuctions) {
                foreach ($theseAuctions as $auction) {
                    if (!isset($newLimitedAuctions[$shop])) {
                        $newLimitedAuctions[$shop] = [];
                    }
                    if ($auction["timeCreated"] > $now) {
                        $empty = false;
                        array_push($newLimitedAuctions[$shop], $auction);
                    }
                }
            }
            if ($empty) {
                $returner["auctionsInTimePeroid"] = false;
            } else {
                $returner["auctionsInTimePeroid"] = true;
            }
            $returner["averageAuctionPrice"] = $auctionSumPerPc / $auctionCount;
            $returner["averageAuctionPricePerStack"] =
                ($auctionSumPerPc / $auctionCount) * 64;
            $returner["lowestAuctionPrice"] = $lowestAuction;
            $returner["limitedAuctions"] = $newLimitedAuctions;
        } else {
            $returner["hasLimitedAuctions"] = false;
        }

        return $returner;
    }

    public function setAuctionReliable($auctionId, $reliable)
    {
        $reliable = $reliable ? 0 : 1;
        $this->checkAuctionsTable();
        $this->query(
            "UPDATE auctions SET reliable = '$reliable' WHERE id = '$auctionId'",
        );
    }

    public function setAuctionNotMaintained($auctionId, $notMaintained)
    {
        $notMaintained = $notMaintained ? 0 : 1;
        $this->checkAuctionsTable();
        $this->query(
            "UPDATE auctions SET notMaintained = '$notMaintained' WHERE id = '$auctionId'",
        );
    }

    public function setAuctionMostlyAvailable($auctionId, $mostlyAvailable)
    {
        $mostlyAvailable = $mostlyAvailable ? 0 : 1;
        $this->checkAuctionsTable();
        $this->query(
            "UPDATE auctions SET mostlyAvailable = '$mostlyAvailable' WHERE id = '$auctionId'",
        );
    }

    public function getTokens($shopId)
    {
        $this->checkTokensTable();
        $res = $this->query("SELECT token FROM tokens WHERE shop = '$shopId'");
        if (count($res) == 0) {
            return [];
        }
        return $res;
    }

    public function generateToken($shopId, $userId)
    {
        $this->checkTokensTable();
        $token = bin2hex(random_bytes(12));

        $this->query(
            "INSERT INTO tokens (token, shop, creator) VALUES ('$token', '$shopId', '$userId')",
        );
        return $token;
    }

    public function deleteToken($token)
    {
        $this->checkTokensTable();
        $this->query("DELETE FROM tokens WHERE token = '$token'");
    }

    public function validateToken($token)
    {
        $this->checkTokensTable();
        $res = $this->query("SELECT * FROM tokens WHERE token = '$token'");
        if (count($res) == 0) {
            return false;
        }
        return true;
    }

    public function addLimitedAuctions($token, $data)
    {
        $this->checkTokensTable();
        $this->checkAuctionsTable();
        $res = $this->query(
            "SELECT shop, creator FROM tokens WHERE token = '$token'",
        );
        if (count($res) == 0) {
            return false;
        }
        $shopId = $res[0]["shop"];
        $creator = $res[0]["creator"];

        foreach ($data as $entry) {
            $mil = $entry->timestamp;
            $seconds = ceil($mil / 1000);
            $timestamp = date("Y-m-d H:i:s", $seconds);
            $this->query(
                "INSERT IGNORE INTO auctions (
                    item, 
                    price, 
                    shopId, 
                    creator, 
                    amount, 
                    timeCreated,
                    notMaintained, 
                    reliable, 
                    mostlyAvailable,
                    identifier

                ) VALUES (
                    '$entry->item',
                    '$entry->price', 
                    '$shopId', 
                    '$creator', 
                    '$entry->amount', 
                    '$timestamp',
                    (SELECT defaultNotMaintained FROM shops WHERE id = '$shopId'),
                    (SELECT defaultReliable FROM shops WHERE id = '$shopId'),
                    (SELECT defaultMostlyAvailable FROM shops WHERE id = '$shopId'),
                    '$entry->identifier'
                 )",
            );
        }

        $this->updateTrendingCache();
        return true;
    }
}
