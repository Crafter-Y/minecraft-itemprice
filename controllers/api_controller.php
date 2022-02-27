<?php
class ApiController extends AppController
{
    public $uses = ["Lifecycle"];

    public function beforeAction()
    {
        parent::beforeAction();

        $this->set("json", true);
    }

    public function index()
    {
        $data = [
            "error" => false,
            "data" => [
                "endpoints" => [
                    "/api/addLimitedAuction/:token" => [
                        "method" => "POST",
                        "params" => [
                            "item" => "string",
                            "price" => "integer",
                            "amount" => "integer",
                            "timestamp" => "timestamp",
                            "identifier" => "string",
                        ],
                    ],
                    "/api/addLimitedAuctions/:token" => [
                        "method" => "POST",
                        "params" => [
                            "data" => "array",
                            [
                                "item" => "string",
                                "price" => "number",
                                "amount" => "integer",
                                "timestamp" => "timestamp",
                                "identifier" => "string",
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->set("data", $data);
    }

    public function addLimitedAuction()
    {
        $this->set("data", [
            "error" => true,
            "data" => [
                "message" => "This is not implemented yet.",
            ],
        ]);
    }

    public function addLimitedAuctions($token = false)
    {
        if (!$token) {
            $this->set("data", [
                "error" => true,
                "data" => [
                    "message" => "No token provided.",
                ],
            ]);
            return;
        }

        if (!$this->Lifecycle->validateToken($token)) {
            $this->set("data", [
                "error" => true,
                "data" => [
                    "message" => "Invalid token.",
                ],
            ]);
            return;
        }

        if (!isset($this->params["form"])) {
            $this->set("data", [
                "error" => true,
                "data" => [
                    "message" => "Post data not Provided",
                ],
            ]);
            return;
        }

        if (!isset($this->params["form"]["data"])) {
            $this->set("data", [
                "error" => true,
                "data" => [
                    "message" => "Wrong data format",
                ],
            ]);
            return;
        }

        $data = json_decode($this->params["form"]["data"]);

        if (!is_array($data)) {
            $this->set("data", [
                "error" => true,
                "data" => [
                    "message" => "Wrong data format",
                ],
            ]);
            return;
        }

        foreach ($data as $item) {
            if (
                !isset($item->item) ||
                !isset($item->price) ||
                !isset($item->amount) ||
                !isset($item->timestamp) ||
                !isset($item->identifier)
            ) {
                $this->set("data", [
                    "error" => true,
                    "data" => [
                        "message" => "Wrong data format",
                    ],
                ]);
                return;
            }
        }

        $this->Lifecycle->addLimitedAuctions($token, $data);

        $this->set("data", [
            "error" => false,
            "data" => [
                "message" => "Success",
            ],
        ]);
    }
}
