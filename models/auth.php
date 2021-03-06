<?php

class Auth extends AppModel
{
    public function createRootAccount(string $username, string $password)
    {
        $this->query(
            "UPDATE `shop_schema` SET `v` = '1' WHERE `k` = 'rootAccountCreated'",
        );

        return $this->createAccount($username, $password, "root");
    }

    public function login(string $username, string $password)
    {
        $returner = [
            "success" => true,
            "error" => "",
        ];

        $res = $this->query(
            "SELECT * FROM `users` WHERE `username` = '" . $username . "'",
        );
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
        $returner["id"] = $res[0]["id"];
        return $returner;
    }

    public function createAccount(
        string $username,
        string $password,
        string $role
    ) {
        $this->query(
            "INSERT INTO `users` (`username`, `password`, `role`) VALUES ('" .
                $username .
                "', '" .
                password_hash($password, PASSWORD_DEFAULT) .
                "', '" .
                $role .
                "')",
        );
        return $this->query(
            "SELECT `id` FROM `users` WHERE `username` = '" . $username . "'",
        );
    }
}
