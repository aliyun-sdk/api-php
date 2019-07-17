<?php

namespace aliyunsdk\api;

use GuzzleHttp\HandlerStack;
use function GuzzleHttp\Psr7\parse_query;
use Psr\Http\Message\RequestInterface;

/**
 * Class Client
 * @package aliyunsdk\api
 */
class Client extends \GuzzleHttp\Client
{
    /**
     * APP Key
     *
     * @var string
     */
    protected $appKey;

    /**
     * APP Secret Key
     *
     * @var string
     */
    protected $appSecret;

    /**
     * APP Code
     *
     * @var string
     */
    protected $appCode;

    /**
     * 请求环境
     *
     * @var string
     */
    protected $stage;

    /**
     * Client constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->appKey = $config["app_key"] ?? "";
        $this->appSecret = $config["app_secret"] ?? "";
        $this->appCode = $config["app_code"] ?? "";
        $this->stage = $config["stage"] ?? "RELEASE";

        if (!$this->appCode && (!$this->appKey || !$this->appSecret))
        {
            throw new \Exception("app key and secret is required.");
        }

        unset($config["app_key"], $config["app_secret"], $config["app_code"], $config["stage"]);

        $config["handler"] = HandlerStack::create();
        $config["handler"]->push($this->addHeaders());

        parent::__construct($config);
    }

    /**
     * 添加头信息
     *
     * @return \Closure
     */
    private function addHeaders(): \Closure
    {
        return function (callable $handler)
        {
            return function (RequestInterface $request, array $options) use ($handler)
            {
                $request = $request
                    ->withHeader("X-Ca-Stage", $this->stage)
                    ->withHeader("X-Ca-Timestamp", time() * 1000)
                    ->withHeader("X-Ca-Nonce", uniqid($this->appKey, true));

                if ($this->appCode)
                {
                    // 简单认证方式, 建议使用HTTPS协议增强安全性
                    $request = $request->withHeader("Authorization", "APPCODE {$this->appCode}");
                }
                else
                {
                    // 签名认证方式, 计算签名添加签名头, 更安全
                    $signHeaders = [
                        "X-Ca-Key",
                        "X-Ca-Nonce",
                        "X-Ca-Stage",
                        "X-Ca-Timestamp",
                    ];

                    sort($signHeaders);

                    $request = $request
                        ->withHeader("X-Ca-Key", $this->appKey)
                        ->withHeader("X-Ca-Signature-Headers", implode(",", $signHeaders));

                    $request = $request->withHeader("X-Ca-Signature", $this->generateSignature($request, $signHeaders));
                }

                return $handler($request, $options);
            };
        };
    }

    /**
     * 计算安全签名
     *
     * @param RequestInterface $request
     * @param array $signHeaders
     * @return string
     */
    private function generateSignature(RequestInterface $request, array $signHeaders): string
    {
        $glue = "\n";

        $headerString = implode($glue, array_map(
            function ($name) use ($request): string
            {
                return $name . ":" . $request->getHeaderLine($name);
            },
            $signHeaders
        ));

        $signUrlString = $request->getUri()->getPath();

        $requestParams = parse_query($request->getUri()->getQuery());

        if ($request->getHeaderLine("Content-Type") == "application/x-www-form-urlencoded")
        {
            $requestParams = array_merge(parse_query($request->getBody()), $requestParams);
        }

        if (!empty($requestParams))
        {
            $signUrlString .= '?';

            ksort($requestParams);

            array_walk($requestParams, function ($value, $name) use (&$signUrlString)
            {
                $signUrlString .= $name . ($value ? "={$value}&" : "&");
            });
        }

        $signItems = [
            $request->getMethod(),
            $request->getHeaderLine("Accept"),
            $request->getHeaderLine("Content-MD5"),
            $request->getHeaderLine("Content-Type"),
            $request->getHeaderLine("Date"),
            $headerString,
            rtrim($signUrlString, "&"),
        ];

        return base64_encode(hash_hmac('sha256', implode($glue, $signItems), $this->appSecret, true));
    }
}