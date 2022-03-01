<?php
class AdminController extends AppController
{
    public $components = ["Session"];
    public $uses = ["Auth", "Lifecycle"];

    public function beforeAction()
    {
        parent::beforeAction();

        $this->set("loggedIn", $this->Session->read("loggedIn"));
        $this->set("username", $this->Session->read("username"));
        $this->set("role", $this->Session->read("role"));
    }

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

    public function index()
    {
        if (!$this->checkLoggedIn()) {
            $this->redirect("admin/login");
        }
        $this->set("shops", $this->Lifecycle->getShops());
        $canEditShopSchema = $this->Lifecycle->isAdminAllowedToEditShop();
        if ($this->Session->read("role") == "root") {
            $canEditShopSchema = true;
        }
        $this->set("canEditShop", $canEditShopSchema);
    }

    public function login()
    {
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
                $this->Session->write("userId", $res["id"]);
                $this->Session->write("loggedIn", true);

                if ($this->checkLoggedIn()) {
                    $this->redirect("admin/index");
                } else {
                    $this->set(
                        "error",
                        "You don't have the permissions to pass by.",
                    );
                }
            } else {
                $this->set("error", $res["error"]);
            }
        }
    }

    public function rootPanel()
    {
        if ($this->Session->read("role") != "root") {
            $this->redirect("admin/index");
        }
        if (isset($this->params["form"]["form1"])) {
            $username = $this->params["form"]["username"];
            $password1 = $this->params["form"]["password1"];
            $password2 = $this->params["form"]["password2"];
            $role = $this->params["form"]["role"];
            if (preg_match("/^[a-zA-Z_\-0-9]{5,24}$/", $username) == 1) {
                if ($password1 == $password2) {
                    if (
                        preg_match(
                            "/^[a-zA-Z_\-0-9!\§\$\%\&\/\(\)\=\?\+\#_\-]{7,64}$/",
                            $password1,
                        ) == 1
                    ) {
                        if (preg_match("/^user|admin|root$/", $role) == 1) {
                            try {
                                $this->Auth->createAccount(
                                    $username,
                                    $password1,
                                    $role,
                                );
                                $this->set("success", "Account created!");
                            } catch (Exception $e) {
                                $this->set(
                                    "error",
                                    "This Account is already existing!",
                                );
                            }
                        } else {
                            $this->set("error", "You need to specify a role.");
                        }
                    } else {
                        $this->set(
                            "error",
                            "Password must be at least 7 characters long and can only contain the following characters: a-Z, 0-9, _, -, !, §, $, %, &, /, (, ), =, ?, +, #, _",
                        );
                    }
                } else {
                    $this->set("error", "Passwords do not match");
                }
            } else {
                $this->set(
                    "error",
                    "Username must be between 5 and 24 characters long and contain only letters, numbers and _-",
                );
            }
        }

        if (isset($this->params["form"]["form2"])) {
            $name = $this->params["form"]["name"];
            $description = $this->params["form"]["description"];
            $owner = $this->params["form"]["owner"];
            $notMaintained = isset($this->params["form"]["notMaintained"])
                ? 1
                : 0;
            $reliable = isset($this->params["form"]["reliable"]) ? 1 : 0;
            $mostlyAvailable = isset($this->params["form"]["mostlyAvailable"])
                ? 1
                : 0;
            $limited = isset($this->params["form"]["limited"]) ? 1 : 0;

            $this->Lifecycle->createShop(
                $name,
                $description,
                $this->Session->read("userId"),
                $owner,
                $notMaintained,
                $reliable,
                $mostlyAvailable,
                $limited,
            );
            $this->set("success2", "Shop created!");
        }

        if (isset($this->params["form"]["form3"])) {
            $defaultUserAccess = isset(
                $this->params["form"]["defaultUserAccess"],
            );
            $isAdminAllowedToEditShop = isset(
                $this->params["form"]["isAdminAllowedToEditShop"],
            );
            $this->Lifecycle->setDefaultUserAccess($defaultUserAccess);
            $this->Lifecycle->setAdminAllowedToEditShop(
                $isAdminAllowedToEditShop,
            );
        }
        $this->set(
            "defaultUserAccess",
            $this->Lifecycle->isDefaultUserAllowedToViewMainController(),
        );
        $this->set(
            "isAdminAllowedToEditShop",
            $this->Lifecycle->isAdminAllowedToEditShop(),
        );
    }

    public function editShop($shopId = false)
    {
        $searchQuery = false;
        if (!$this->checkLoggedIn()) {
            $this->redirect("admin/login");
        }
        if (!$shopId) {
            $this->redirect("admin/index");
        }

        if (
            isset($this->params["url"]["search"]) &&
            $this->params["url"]["search"]
        ) {
            $searchQuery = $this->params["url"]["search"];
            $this->set("searchQuery", $searchQuery);
        }
        $shop = $this->Lifecycle->getShop($shopId, $searchQuery);
        if (!$shop) {
            $this->redirect("admin/index");
        }

        if (isset($this->params["form"]["form2"])) {
            $item = $this->params["form"]["item"];
            $price = $this->params["form"]["price"];
            $amount = $this->params["form"]["amount"];
            $creator = $this->Session->read("userId");
            $this->set("lastPrice", $price);
            $this->set("lastAmount", $amount);

            if ($item == "Choose Item") {
                $this->set("error", "Please choose an item.");
            } else {
                $this->Lifecycle->createAuction(
                    $item,
                    $price,
                    $shopId,
                    $creator,
                    $amount,
                );
                $this->set("success", "Auction Created!");
                $shop = $this->Lifecycle->getShop($shopId, $searchQuery);
            }
        }

        if (isset($this->params["form"]["form3"])) {
            $auctionId = $this->params["form"]["auctionId"];
            $this->Lifecycle->deleteAuction($auctionId);
            $shop = $this->Lifecycle->getShop($shopId, $searchQuery);
        }

        if (isset($this->params["form"]["reliableBtn"])) {
            $auctionId = $this->params["form"]["auctionId"];
            $relibale = $this->params["form"]["reliable"];
            $this->Lifecycle->setAuctionReliable($auctionId, $relibale);
            $shop = $this->Lifecycle->getShop($shopId, $searchQuery);
        }

        if (isset($this->params["form"]["mostlyAvailableBtn"])) {
            $auctionId = $this->params["form"]["auctionId"];
            $mostlyAvailable = $this->params["form"]["mostlyAvailable"];
            $this->Lifecycle->setAuctionMostlyAvailable(
                $auctionId,
                $mostlyAvailable,
            );
            $shop = $this->Lifecycle->getShop($shopId, $searchQuery);
        }

        if (isset($this->params["form"]["notMaintainedBtn"])) {
            $auctionId = $this->params["form"]["auctionId"];
            $notMaintained = $this->params["form"]["notMaintained"];
            $this->Lifecycle->setAuctionNotMaintained(
                $auctionId,
                $notMaintained,
            );
            $shop = $this->Lifecycle->getShop($shopId, $searchQuery);
        }
        $this->set("shop", $shop);
    }

    public function hardReset()
    {
        if (!$this->checkLoggedIn()) {
            $this->redirect("admin/login");
        }
        if ($this->Session->read("role") != "root") {
            $this->redirect("admin/index");
        }
        $this->Lifecycle->hardReset();
        $this->redirect("main/initialSetup");
    }

    public function configureShop($shopId = false)
    {
        if (!$this->checkLoggedIn()) {
            $this->redirect("admin/login");
        }
        if (
            $this->Session->read("role") == "admin" &&
            !$this->Lifecycle->isAdminAllowedToEditShop()
        ) {
            $this->redirect("admin/index");
        }

        if (!$shopId) {
            $this->redirect("admin/index");
        }

        if (isset($this->params["form"]["form1"])) {
            $name = $this->params["form"]["name"];
            $description = $this->params["form"]["description"];
            $owner = $this->params["form"]["owner"];
            $notMaintained = isset($this->params["form"]["notMaintained"])
                ? 1
                : 0;
            $reliable = isset($this->params["form"]["reliable"]) ? 1 : 0;
            $mostlyAvailable = isset($this->params["form"]["mostlyAvailable"])
                ? 1
                : 0;
            $limited = isset($this->params["form"]["limited"]) ? 1 : 0;
            $this->Lifecycle->configureShop(
                $shopId,
                $name,
                $description,
                $owner,
                $notMaintained,
                $reliable,
                $mostlyAvailable,
                $limited,
            );
        }

        if (isset($this->params["form"]["form2"])) {
            $this->Lifecycle->deleteShop($shopId);
            $this->redirect("admin/index");
        }

        if (isset($this->params["form"]["form3"])) {
            $this->Lifecycle->generateToken(
                $shopId,
                $this->Session->read("userId"),
            );
        }

        if (isset($this->params["form"]["form4"])) {
            $token = $this->params["form"]["token"];
            $this->Lifecycle->deleteToken($token);
        }

        $shop = $this->Lifecycle->getShop($shopId, false);
        $tokens = $this->Lifecycle->getTokens($shopId);
        if (!$shop) {
            $this->redirect("admin/index");
        }
        $this->set("shop", $shop);
        $this->set("tokens", $tokens);
    }
}
