<?php

include "../vendor/autoload.php";

use AliyunSDK\Api\Client;

class TestClient extends Client
{
    public function ping()
    {
        return $this->post("/ping");
    }
}

// 完全兼容GuzzleHttp\Client的用法

$testSVR = new TestClient([
    "base_uri" => "您的API地址",

    // 配置了app_code启用简单认证，则无需提供app_key和app_secret
    // 相反若提供了app_key和app_secret也无需再提供app_code
    // 两者app_code优先

    "app_key" => "您的APP KEY",
    "app_secret" => "您的APP SECRET",
    "app_code" => "您的APP CODE",

    "query" => [
        "params1" => 1,
    ],

    "form_params" => [
        "params2" => 2,
    ],

    'body' => "my content",
]);

echo $testSVR->ping()->getBody()->getContents();