<?php
class MainController extends AppController
{
    public $uses = array("Lifecycle");

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
        if (isset($this->params["form"])) {
            // TODO: implement creating the root account
            var_dump($this->params["form"]);
        }
    }
   
}
