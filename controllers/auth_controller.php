<?php
class AuthController extends AppController
{
    public $uses = ["Auth"];
    public $components = ["Session"];

    private function checkLoggedIn()
    {
        if (!$this->Session->read("loggedIn")) {
            return false;
        }
        if (
            $this->Session->read("role") != "root" &&
            $this->Session->read("role") != "admin"
        ) {
            return false;
        }
        return true;
    }

    public function login()
    {
        if ($this->checkLoggedIn()) {
            $this->redirect("main/index");
        }
        if (isset($this->params["form"])) {
            $username = $this->params["form"]["username"];
            $password = $this->params["form"]["password"];
            $res = $this->Auth->login($username, $password);
            if ($res["success"]) {
                $this->Session->write("role", $res["role"]);
                $this->Session->write("username", $username);
                $this->Session->write("userId", $res["id"]);
                $this->Session->write("loggedIn", true);

                $this->redirect("main/index");
            } else {
                $this->set("error", $res["error"]);
            }
        }
    }
}
