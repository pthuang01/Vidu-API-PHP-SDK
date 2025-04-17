<?php

namespace Vidu\SDK\Http;

// 引入所有特定的例外類別
use Vidu\SDK\Exceptions\AuthenticationException;
use Vidu\SDK\Exceptions\InsufficientCreditsException;
use Vidu\SDK\Exceptions\InternalServerException; // 引入 InternalServerException
use Vidu\SDK\Exceptions\InvalidRequestException;
use Vidu\SDK\Exceptions\NotFoundException;
use Vidu\SDK\Exceptions\PermissionDeniedException;
use Vidu\SDK\Exceptions\RateLimitExceededException;
use Vidu\SDK\Exceptions\ViduApiException; // 也要引入基礎例外

/**
 * Vidu API HTTP 客戶端
 *
 * 負責處理與 Vidu API 的 HTTP 通信。
 */
class Client
{
    /**
     * API 基礎 URL
     * @var string
     */
    private $baseUrl = 'https://api.vidu.com/ent/v2';

    /**
     * 工具 API 基礎 URL
     * @var string
     */
    private $toolsBaseUrl = 'https://api.vidu.com/tools/v2';

    /**
     * 您的 API 金鑰
     * @var string
     */
    private $apiKey;

    /**
     * HTTP 客戶端選項 (例如 cURL 選項)
     * @var array
     */
    private $options = [];

    /**
     * Client 建構子。
     *
     * @param string $apiKey 您的 Vidu API 金鑰。
     * @param array $options 可選的 HTTP 客戶端選項。
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API Key 不能為空。');
        }
        $this->apiKey = $apiKey;
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * 取得預設的 cURL 選項。
     *
     * @return array 預設選項。
     */
    private function getDefaultOptions(): array
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30, // 請求超時時間 (秒)
            CURLOPT_CONNECTTIMEOUT => 10, // 連接超時時間 (秒)
            CURLOPT_HTTPHEADER => [],
            CURLOPT_HEADER => true, // 需要包含標頭以便解析
        ];
    }

    /**
     * 發送 HTTP 請求。
     *
     * @param string $method HTTP 方法 (GET, POST, PUT, DELETE)。
     * @param string $endpoint API 端點路徑 (相對於 baseUrl 或 toolsBaseUrl) 或完整 URL。
     * @param array|null $data 請求資料 (對於 POST 和 PUT)。
     * @param array $queryParams GET 請求的查詢參數。
     * @param array $headers 額外的請求標頭。
     * @param bool $isToolsApi 指示是否使用工具 API 的基礎 URL。
     * @return array API 回應的關聯陣列。
     * @throws ViduApiException 如果 API 請求失敗 (可能是特定的子類別)。
     */
    public function request(string $method, string $endpoint, ?array $data = null, array $queryParams = [], array $headers = [], bool $isToolsApi = false): array
    {
        $url = $this->buildUrl($endpoint, $queryParams, $isToolsApi);
        $isExternalUrl = filter_var($endpoint, FILTER_VALIDATE_URL) && strpos($endpoint, $this->baseUrl) !== 0 && strpos($endpoint, $this->toolsBaseUrl) !== 0;

        $options = $this->prepareCurlOptions($method, $url, $data, $headers, $isExternalUrl);

        $curl = curl_init();
        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        //$responseHeaders = substr($response, 0, $headerSize);
        $responseBody = $headerSize ? substr($response, $headerSize) : $response; // Handle no header case

        if ($response === false) {
            $error = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);
            // cURL 錯誤，通常是網路層問題，使用基礎 ViduApiException
            throw new ViduApiException("cURL 請求失敗 ({$errno}): {$error}", 0);
        }

        curl_close($curl);

        $decodedBody = null;
        if (!empty($responseBody)) {
            $decodedBody = json_decode($responseBody, true);
            // 檢查 JSON 解碼錯誤
            if (json_last_error() !== JSON_ERROR_NONE) {
                // 如果狀態碼是成功範圍但 body 無法解析 JSON，可能是一個特殊的成功場景
                if ($httpCode >= 200 && $httpCode < 300) {
                    // Keep decodedBody as null, let the later check handle it or return raw
                } else {
                    // 如果是錯誤狀態碼且無法解析 JSON，拋出基礎例外
                    throw new ViduApiException("無法解析 API 回應 JSON: " . json_last_error_msg() . "
原始回應: {$responseBody}", $httpCode);
                }
            }
        }

        // 檢查 API 錯誤碼並拋出特定例外
        if ($httpCode >= 400) {
            $this->handleApiError($httpCode, $decodedBody, $responseBody);
        }

        // 如果 decodedBody 為 null 且 HTTP 狀態碼成功，可能是一個沒有 body 的成功回應 (例如 Cancel)
        if ($decodedBody === null && $httpCode >= 200 && $httpCode < 300) {
             return ['status' => 'success', 'http_code' => $httpCode, 'raw_body' => $responseBody];
        }

        // 確保返回的是陣列
        if (!is_array($decodedBody)) {
             throw new ViduApiException("API 回應不是有效的 JSON 物件/陣列。 Body: {$responseBody}", $httpCode);
        }

        return $decodedBody;
    }

     /**
     * 處理 API 錯誤，根據 HTTP Code 和 Error Code 拋出特定例外。
     *
     * @param int $httpCode HTTP 狀態碼。
     * @param array|null $decodedBody 解碼後的 API 回應內容。
     * @param string $rawBody 原始回應 Body。
     * @throws ViduApiException (或其子類別)。
     */
    protected function handleApiError(int $httpCode, ?array $decodedBody, string $rawBody): void
    {
        // 嘗試從 decodedBody 或 rawBody (如果解碼失敗) 中獲取錯誤訊息
        $errorMessage = $decodedBody['error_message'] ?? ($decodedBody['detail'] ?? '未知的 API 錯誤');
        if ($errorMessage === '未知的 API 錯誤' && !empty($rawBody)) {
             $errorMessage .= " Raw Body: " . mb_substr($rawBody, 0, 500); // 包含部分原始 body 內容
        }
        $errorCode = $decodedBody['error_code'] ?? null;

        $exceptionClass = ViduApiException::class; // 預設基礎例外

        switch ($httpCode) {
            case 400:
                if ($errorCode === 'CreditInsufficient') {
                    $exceptionClass = InsufficientCreditsException::class;
                } else {
                    // 其他 400 錯誤歸類為 InvalidRequestException
                    $exceptionClass = InvalidRequestException::class;
                }
                break;
            case 401:
                $exceptionClass = AuthenticationException::class;
                break;
            case 403:
                $exceptionClass = PermissionDeniedException::class;
                break;
            case 404:
                $exceptionClass = NotFoundException::class;
                break;
            case 429:
                $exceptionClass = RateLimitExceededException::class;
                break;
            case 500:
                 $exceptionClass = InternalServerException::class;
                 break;
            // 可以在此處添加更多 HTTP 狀態碼的處理，例如 409 Conflict
            // case 409:
            //     $exceptionClass = ConflictException::class; // 需要先定義
            //     break;
        }

        // 創建並拋出對應的例外實例
        throw new $exceptionClass($errorMessage, $httpCode, $errorCode, $decodedBody);
    }

    /**
     * 建構完整的請求 URL。
     *
     * @param string $endpoint API 端點或完整 URL。
     * @param array $queryParams 查詢參數。
     * @param bool $isToolsApi 是否使用工具 API 的 URL。
     * @return string 完整的 URL。
     */
    private function buildUrl(string $endpoint, array $queryParams = [], bool $isToolsApi = false): string
    {
        // 如果 endpoint 已經是完整的 URL，直接返回
        if (filter_var($endpoint, FILTER_VALIDATE_URL)) {
            // 如果需要添加 queryParams 到已有的 URL
            if (!empty($queryParams)) {
                 $queryString = http_build_query($queryParams);
                 // 檢查 URL 是否已有查詢字串
                 if (strpos($endpoint, '?') !== false) {
                     return $endpoint . '&' . $queryString;
                 } else {
                     return $endpoint . '?' . $queryString;
                 }
            }
            return $endpoint;
        }

        $baseUrl = $isToolsApi ? $this->toolsBaseUrl : $this->baseUrl;
        
        // 移除 baseUrl 可能的結尾斜線和 endpoint 可能的開頭斜線
        $baseUrl = rtrim($baseUrl, '/');
        $endpointPath = ltrim($endpoint, '/');
        $url = $baseUrl . '/' . $endpointPath;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        return $url;
    }

    /**
     * 準備 cURL 選項。
     *
     * @param string $method HTTP 方法。
     * @param string $url 請求 URL。
     * @param array|null $data 請求資料。
     * @param array $headers 額外標頭。
     * @param bool $isExternalUrl 是否為外部 URL (例如 S3 上傳連結)。
     * @return array cURL 選項陣列。
     */
    private function prepareCurlOptions(string $method, string $url, ?array $data, array $headers, bool $isExternalUrl): array
    {
        $options = $this->options;
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);

        $finalHeaders = $this->options[CURLOPT_HTTPHEADER] ?? [];

        // 只有內部 API URL 需要自動添加認證和預設 Content-Type/Accept
        if (!$isExternalUrl) {
            $defaultInternalHeaders = [
                'Authorization: Token ' . $this->apiKey,
                'Accept: application/json',
            ];
            // 只有在有 data 且不是 GET/DELETE 時才預設 Content-Type: application/json
            if ($data !== null && !in_array(strtoupper($method), ['GET', 'DELETE'])) {
                 $defaultInternalHeaders[] = 'Content-Type: application/json';
            }
             $finalHeaders = array_merge($defaultInternalHeaders, $finalHeaders);
        }

        // 合併傳入的 $headers (優先級最高)
        $finalHeaders = array_merge($finalHeaders, $headers);

        // 去重，保留最後出現的標頭 (基於 key)
        $headerMap = [];
        foreach ($finalHeaders as $header) {
             // 處理可能沒有值的標頭 (雖然少見)
             if (strpos($header, ':') !== false) {
                 list($key) = explode(':', $header, 2);
                 $headerMap[strtolower(trim($key))] = $header;
             } else {
                  // 如果沒有冒號，可能是一個標誌性標頭，直接加入
                  $headerMap[strtolower(trim($header))] = $header;
             }
        }
        $options[CURLOPT_HTTPHEADER] = array_values($headerMap);

        // 處理請求 Body
        if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            if ($jsonData === false) {
                throw new \InvalidArgumentException('無法將資料編碼為 JSON: ' . json_last_error_msg());
            }
            $options[CURLOPT_POSTFIELDS] = $jsonData;
        }

        // 對於 GET, DELETE 等方法，不應設置 CURLOPT_POSTFIELDS
        if (in_array(strtoupper($method), ['GET', 'DELETE'])) {
            unset($options[CURLOPT_POSTFIELDS]);
        }

        return $options;
    }

     /**
     * 發送檔案上傳請求 (特殊流程 - 上傳到外部 URL，通常是 S3)。
     *
     * @param string $url 上傳目標 URL。
     * @param string $filePath 要上傳的檔案路徑。
     * @param string $contentType 檔案的 Content-Type。
     * @return array 包含 ETag 和原始標頭的陣列。
     * @throws ViduApiException 如果上傳失敗。
     * @throws \InvalidArgumentException 如果檔案無效。
     */
    public function uploadFile(string $url, string $filePath, string $contentType): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("檔案不存在: {$filePath}");
        }
        if (!is_readable($filePath)) {
             throw new \InvalidArgumentException("檔案無法讀取: {$filePath}");
        }

        $fileHandle = fopen($filePath, 'r');
        if (!$fileHandle) {
            // 使用基礎 ViduApiException，因為這不是 API 錯誤
            throw new ViduApiException("無法開啟檔案: {$filePath}");
        }

        $curl = curl_init();
        // 檔案上傳到外部 URL 通常只需要 Content-Type
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300, // 增加上傳超時時間
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $fileHandle,
            CURLOPT_INFILESIZE => filesize($filePath),
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $contentType, // 只需要 Content-Type
            ],
            CURLOPT_HEADER => true, // 需要讀取回應標頭以獲取 ETag
        ];
        // 合併用戶自訂的 cURL 選項 (如果有)
        $options = array_replace($this->options, $options); 
        // 確保 HTTPHEADER 正確合併，而不是完全替換
        $options[CURLOPT_HTTPHEADER] = array_merge($this->options[CURLOPT_HTTPHEADER] ?? [], ['Content-Type: ' . $contentType]);
        // 去重 (Content-Type 以我們的為準)
        $headerMap = [];
        foreach ($options[CURLOPT_HTTPHEADER] as $header) {
             list($key) = explode(':', $header, 2);
             $headerMap[strtolower(trim($key))] = $header;
        }
        $options[CURLOPT_HTTPHEADER] = array_values($headerMap);


        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeaders = $headerSize ? substr($response, 0, $headerSize) : '';
        $responseBody = $headerSize ? substr($response, $headerSize) : $response;

        if ($response === false) {
            $error = curl_error($curl);
            $errno = curl_errno($curl);
            fclose($fileHandle);
            curl_close($curl);
            throw new ViduApiException("檔案上傳 cURL 請求失敗 ({$errno}): {$error}");
        }

        fclose($fileHandle);
        curl_close($curl);

        // 外部 URL 上傳失敗
        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = "檔案上傳失敗 (HTTP Code: {$httpCode})。Response Body: " . mb_substr($responseBody, 0, 500);
            // 雖然不是 Vidu API 本身的錯誤，但為了統一處理，仍拋出 ViduApiException
            throw new ViduApiException($errorMessage, $httpCode);
        }

        // 從回應標頭解析 ETag
        $etag = null;
        $headersArray = explode("
", $responseHeaders);
        foreach ($headersArray as $headerLine) {
            if (stripos($headerLine, 'ETag:') === 0) {
                // S3 的 ETag 通常帶有引號，需要去除
                $etag = trim(substr($headerLine, 5), ' "');
                break;
            }
        }

        if ($etag === null) {
            // 如果沒有 ETag，可能是非 S3 的上傳目標，或者上傳配置問題
            throw new ViduApiException('無法從上傳回應中獲取 ETag。 Headers: ' . $responseHeaders);
        }

        return ['etag' => $etag, 'headers' => $responseHeaders];
    }
}
