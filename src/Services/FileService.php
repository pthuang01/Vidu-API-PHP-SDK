<?php

namespace Vidu\SDK\Services;

use Vidu\SDK\Exceptions\ViduApiException;

/**
 * Vidu 檔案上傳服務
 *
 * 處理圖片上傳的特殊三步驟流程。
 */
class FileService extends AbstractService
{
    /**
     * 步驟 1：建立上傳連結。
     *
     * @param string $scene 場景值，根據文件目前為 'vidu'。
     * @return array 包含 'id', 'put_url', 'expires_at' 的陣列。
     * @throws ViduApiException 如果 API 請求失敗。
     */
    public function createUploadLink(string $scene = 'vidu'): array
    {
        $payload = ['scene' => $scene];
        // 注意：此端點相對於工具 API 基礎 URL
        $response = $this->client->request('POST', 'https://api.vidu.com/tools/v2/files/uploads', $payload);
        
        if (!isset($response['id']) || !isset($response['put_url'])) {
             throw new ViduApiException('建立上傳連結的回應缺少必要欄位。', $response['http_code'] ?? 0, null, $response);
        }
        return $response;
    }

    /**
     * 步驟 2：使用上傳連結上傳圖片。
     *
     * @param string $putUrl 從 createUploadLink 獲取的上傳 URL。
     * @param string $filePath 要上傳的圖片檔案路徑。
     * @param string $contentType 圖片的 Content-Type (例如 'image/png', 'image/jpeg')。
     * @return string 上傳後取得的 ETag。
     * @throws ViduApiException 如果上傳失敗。
     * @throws \InvalidArgumentException 如果檔案不存在或無法讀取。
     */
    public function uploadImageToLink(string $putUrl, string $filePath, string $contentType): string
    {
        $uploadResult = $this->client->uploadFile($putUrl, $filePath, $contentType);
        return $uploadResult['etag'];
    }

    /**
     * 步驟 3：完成上傳。
     *
     * @param string $resourceId 從 createUploadLink 獲取的資源 ID。
     * @param string $etag 從 uploadImageToLink 獲取的 ETag。
     * @return array 包含 'uri' 的陣列，該 URI 可用於 API 請求中的 images 欄位。
     * @throws ViduApiException 如果 API 請求失敗。
     */
    public function completeUpload(string $resourceId, string $etag): array
    {
        $payload = ['etag' => $etag];
        // 注意：此端點相對於工具 API 基礎 URL
        $endpoint = "https://api.vidu.com/tools/v2/files/uploads/{$resourceId}/finish";
        $response = $this->client->request('PUT', $endpoint, $payload);

        if (!isset($response['uri'])) {
             throw new ViduApiException('完成上傳的回應缺少 uri 欄位。', $response['http_code'] ?? 0, null, $response);
        }
        return $response;
    }

    /**
     * 整合的上傳圖片方法。
     *
     * 將三個步驟封裝在一起，方便直接呼叫。
     *
     * @param string $filePath 要上傳的圖片檔案路徑。
     * @param string|null $contentType 圖片的 Content-Type。如果為 null，將嘗試自動偵測。
     * @param string $scene 場景值，預設 'vidu'。
     * @return string 上傳完成後的圖片 URI。
     * @throws ViduApiException 如果任何步驟失敗。
     * @throws \InvalidArgumentException 如果檔案不存在或無法讀取，或無法偵測 Content-Type。
     */
    public function uploadImage(string $filePath, ?string $contentType = null, string $scene = 'vidu'): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("檔案不存在: {$filePath}");
        }

        if ($contentType === null) {
            $contentType = mime_content_type($filePath);
            if ($contentType === false || !in_array($contentType, ['image/png', 'image/jpeg', 'image/webp'])) {
                throw new \InvalidArgumentException("無法自動偵測支援的圖片 Content-Type: {$filePath}");
            }
        }

        // 步驟 1: 建立上傳連結
        $uploadLinkData = $this->createUploadLink($scene);
        $putUrl = $uploadLinkData['put_url'];
        $resourceId = $uploadLinkData['id'];

        // 步驟 2: 上傳圖片
        $etag = $this->uploadImageToLink($putUrl, $filePath, $contentType);

        // 步驟 3: 完成上傳
        $completionData = $this->completeUpload($resourceId, $etag);

        return $completionData['uri'];
    }
}
