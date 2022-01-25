<?php

class Auth extends AppModel
{
    public function createRootAccount(String $username, String $password) {
        $this->query("UPDATE shop_schema SET v = '1' WHERE k = 'rootAccountCreated'");
        
        $this->createAccount($username, $password, "root");
    }

    public function login(String $username, String $password) {
        $returner = array(
            "success" => true,
            "error" => ""
        );
        
        $res = $this->query("SELECT username, password, role FROM users WHERE username = '" . $username . "'");
        if (count($res) == 0) {
            $returner["success"] = false;
            $returner["error"] = "Accound could not be found!";
            return $returner;
        }
        if (!password_verify($password, $res[0]["password"])) {
            $returner["success"] = false;
            $returner["error"] = "Wrong password!";
            return $returner;
        }
        $returner["role"] = $res[0]["role"]; 
        $returner["username"] = $res[0]["username"];   
        return $returner;
    }

    public function createAccount (String $username, String $password, String $role) {
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
        $this->query("INSERT INTO users (username, password, role) VALUES ('" . $username . "', '" . password_hash($password, PASSWORD_DEFAULT) . "', '" . $role . "')");
    }
}