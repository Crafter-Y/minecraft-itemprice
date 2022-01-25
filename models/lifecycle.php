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

    public function createRootAccount(String $username, String $password) {
        $this->query("UPDATE shop_schema SET v = '1' WHERE k = 'rootAccountCreated'");
        
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
        }
        $this->query("INSERT INTO users (username, password, role) VALUES ('" . $username . "', '" . password_hash($password, PASSWORD_DEFAULT) . "', 'root')");
        
    }
}