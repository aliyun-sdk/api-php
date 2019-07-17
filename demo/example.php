<?php

include "../vendor/autoload.php";

use aliyunsdk\api\Client;

class PingSVRClient extends Client
{
    public function ping()
    {
        return $this->get("/ping");
    }
}

$testSVR = new PingSVRClient([
    "base_uri" => "API URL",
    "app_key" => "APP KEY",
    "app_secret" => "APP SECRET",
    "app_code" => "APP CODE",
    "query" => [
        "test" => 2,
    ],
    "form_params" => [
        "test1" => 1,
    ],
    'body' => "xx",
]);

echo $testSVR->ping()->getBody()->getContents();