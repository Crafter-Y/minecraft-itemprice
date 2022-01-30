<?php
class MainController extends AppController
{
    public $uses = array("Lifecycle", "Auth");
    public $components = array('Session');

    public function beforeAction() {
        parent::beforeAction();

        $this->set("loggedIn", $this->Session->read("loggedIn"));
        $this->set("username", $this->Session->read("username"));
        $this->set("role", $this->Session->read("role"));

        if (!$this->Lifecycle->isDefaultUserAllowedToViewMainController() && !$this->Session->read("loggedIn")) {
            $this->redirect("auth/login");
        }
    }

    public function index()
    {
        try {
            $res = $this->Lifecycle->getInitialInformationTable();
            if (!$res) {
                $this->redirect("main/initialSetup");
            }
        } catch (Exception $e) {
            $this->redirect("main/databaseNotFound");
        }
        $sortation = false;
        $sorttype = "trending";
        $search = false;

        if (isset($this->params["form"]["form1"])) {
            if (isset($this->params["form"]["sorting"])) {
                $sortation = $this->params["form"]["sorting"];
                $sorttype = $this->params["form"]["sorting"];

                if (isset($this->params["form"]["search"]) && $this->params["form"]["search"] != "") {
                    $search = $this->params["form"]["search"];
                    $this->set("search", $search);
                }
            }
        }
        $this->set("sortation", $sorttype);
        $this->set("content", $this->Lifecycle->getTrending($sortation, $search));
    }

    public function databaseNotFound() {
        try {
            $res = $this->Lifecycle->getInitialInformationTable();
            $this->redirect("main/index");
        } catch (Exception $ignored) {
            
        }
        $this->set("database", getenv("DB_DATABASE"));
    }

    public function initialSetup() {
        try {
            $res = $this->Lifecycle->getInitialInformationTable();
            if ($res) {
                $this->redirect("main/index");
            }
        } catch (Exception $e) {
            $this->redirect("main/databaseNotFound");
        }
        $this->set("error", false);
        if (isset($this->params["form"])) {
            $username = $this->params["form"]["username"];
            $password1 = $this->params["form"]["password1"];
            $password2 = $this->params["form"]["password2"];
            if (preg_match("/^[a-zA-Z_\-0-9]{5,24}$/", $username) == 1) {
                if ($password1 == $password2) {
                    if (preg_match("/^[a-zA-Z_\-0-9!\§\$\%\&\/\(\)\=\?\+\#_\-]{7,64}$/", $password1) == 1) {   
                        $res = $this->Auth->createRootAccount($username, $password1);
                        $this->Session->write("role", "root");
                        $this->Session->write("username", $username);
                        $this->Session->write("userId", $res[0]["id"]);
                        $this->Session->write("loggedIn", true);
                        $this->redirect("main/index");
                    } else {
                        $this->set("error", "Password must be at least 7 characters long and can only contain the following characters: a-Z, 0-9, _, -, !, §, $, %, &, /, (, ), =, ?, +, #, _");
                    }
                } else {
                    $this->set("error", "Passwords do not match");
                }
            } else {
                $this->set("error", "Username must be between 5 and 24 characters long and contain only letters, numbers and _-");
            }
        }
    }

    public function logout() {
        $this->Session->destroy();
        $this->redirect("main/index");
    }
   
}
