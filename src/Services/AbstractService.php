<?php

namespace Vidu\SDK\Services;

use Vidu\SDK\Http\Client;

/**
 * 抽象基礎服務類別
 *
 * 為所有 Vidu API 服務提供基礎功能，主要是共享 HTTP 客戶端。
 */
abstract class AbstractService
{
    /**
     * HTTP 客戶端實例
     * @var Client
     */
    protected $client;

    /**
     * AbstractService 建構子。
     *
     * @param Client $client HTTP 客戶端實例。
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
