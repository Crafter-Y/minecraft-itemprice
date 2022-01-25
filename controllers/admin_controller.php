<?php
class AdminController extends AppController
{
    public $components = array('Session');
    public $uses = array("Auth");

    public function beforeAction() {
        parent::beforeAction();

        $this->set("loggedIn", $this->Session->read("loggedIn"));
        $this->set("username", $this->Session->read("username"));
        $this->set("role", $this->Session->read("role"));
    }

    private function checkLoggedIn () {
        if (!$this->Session->read("loggedIn")) {
            return false;
        }
        if ($this->Session->read("role") != "root" && $this->Session->read("role") != "admin") {
            return false;
        }
        return true;
    }


    public function index()
    {
        if (!$this->checkLoggedIn()) {
            $this->redirect("admin/login");
        }
    }

    public function login() {
        if ($this->checkLoggedIn()) {
            $this->redirect("admin/index");
        }
        if (isset($this->params["form"])) {
            $username = $this->params["form"]["username"];
            $password = $this->params["form"]["password"];
            $res = $this->Auth->login($username, $password);
            if ($res["success"]) {
                $this->Session->write("role", $res["role"]);
                $this->Session->write("username", $username);
                $this->Session->write("loggedIn", true);
                
                if ($this->checkLoggedIn()) {
                    $this->redirect("admin/index");
                } else {
                    $this->set("error", "You don't have the permissions to pass by.");
                }
            } else {
                $this->set("error", $res["error"]);
            }
        }
    }

}