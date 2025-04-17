<?php

namespace Vidu\SDK;

use Vidu\SDK\Http\Client;
use Vidu\SDK\Services\AccountService;
use Vidu\SDK\Services\FileService;
use Vidu\SDK\Services\TaskService;
use Vidu\SDK\Services\VideoService;

/**
 * Vidu PHP SDK 主客戶端類別
 *
 * 這是使用 SDK 的主要入口點，提供了對各個 API 服務的存取方法。
 *
 * @property-read VideoService $videos 存取影片生成服務
 * @property-read TaskService $tasks 存取任務管理服務
 * @property-read AccountService $account 存取帳戶資訊服務
 * @property-read FileService $files 存取檔案上傳服務
 */
class ViduClient
{
    /**
     * HTTP 客戶端實例
     * @var Client
     */
    private $httpClient;

    /**
     * 服務實例快取
     * @var array<string, \Vidu\SDK\Services\AbstractService>
     */
    private $services = [];

    /**
     * ViduClient 建構子。
     *
     * @param string $apiKey 您的 Vidu API 金鑰。
     * @param array $options 可選的 HTTP 客戶端選項 (例如 cURL 選項)。
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $this->httpClient = new Client($apiKey, $options);
    }

    /**
     * 魔術方法，用於延遲載入和存取服務實例。
     *
     * @param string $name 服務名稱 (例如 'videos', 'tasks')。
     * @return \Vidu\SDK\Services\AbstractService 對應的服務實例。
     * @throws \InvalidArgumentException 如果請求的服務不存在。
     */
    public function __get(string $name)
    {
        if (!isset($this->services[$name])) {
            $serviceClass = $this->getServiceClassName($name);
            if ($serviceClass === null) {
                throw new \InvalidArgumentException("未知的服務: {$name}");
            }
            $this->services[$name] = new $serviceClass($this->httpClient);
        }
        return $this->services[$name];
    }

    /**
     * 根據簡短名稱獲取完整的服務類別名稱。
     *
     * @param string $name 服務的簡短名稱。
     * @return string|null 對應的完整類別名稱，如果不存在則返回 null。
     */
    private function getServiceClassName(string $name): ?string
    {
        $map = [
            'videos' => VideoService::class,
            'tasks' => TaskService::class,
            'account' => AccountService::class,
            'files' => FileService::class,
        ];
        return $map[$name] ?? null;
    }

    /**
     * 取得底層的 HTTP 客戶端實例。
     *
     * 主要用於進階使用或測試。
     *
     * @return Client
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }
}
