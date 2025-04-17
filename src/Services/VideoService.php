<?php

namespace Vidu\SDK\Services;

use Vidu\SDK\Exceptions\ViduApiException;
use Vidu\SDK\Models\TaskResponse;

/**
 * Vidu 影片生成服務
 *
 * 處理所有與影片生成相關的 API 端點。
 */
class VideoService extends AbstractService
{
    /**
     * 從圖片生成影片 (Image to Video)。
     *
     * @param string $model 模型名稱 (例如 'vidu2.0', 'vidu1.5', 'vidu1.0')。
     * @param array $images 圖片 URL 或 Base64 編碼字串陣列 (僅限 1 張圖片)。
     * @param array $options 可選參數，例如：
     *   - prompt (string): 文字提示。
     *   - duration (int): 影片時長 (秒)，預設 4 (可選 4, 8)。
     *   - seed (int): 隨機種子。
     *   - resolution (string): 解析度 (例如 '360p', '720p', '1080p')，預設 '360p'。
     *   - movement_amplitude (string): 物件運動幅度 (僅 vidu1.5, vidu2.0)，預設 'auto' (可選 'auto', 'small', 'medium', 'large')。
     *   - callback_url (string): 回調 URL。
     * @return TaskResponse 包含任務 ID 和初始狀態的回應物件。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果輸入參數無效。
     */
    public function imageToVideo(string $model, array $images, array $options = []): TaskResponse
    {
        if (count($images) !== 1) {
            throw new \InvalidArgumentException('Image to Video API 只接受一張圖片。');
        }
        $payload = array_merge(
            ['model' => $model, 'images' => $images],
            $options
        );
        $response = $this->client->request('POST', 'img2video', $payload);
        return new TaskResponse($response);
    }

    /**
     * 根據參考圖片生成影片 (Reference to Video)。
     *
     * @param string $model 模型名稱 (例如 'vidu2.0', 'vidu1.5', 'vidu1.0')。
     * @param array $images 圖片 URL 或 Base64 編碼字串陣列 (vidu1.0 最多 1 張, vidu1.5/2.0 最多 3 張)。
     * @param string $prompt 文字提示。
     * @param array $options 可選參數，例如：
     *   - duration (int): 影片時長 (秒)，預設 4 (vidu2.0 僅支援 4)。
     *   - seed (int): 隨機種子。
     *   - aspect_ratio (string): 寬高比 (例如 '16:9', '9:16', '1:1')，預設 '16:9'。
     *   - resolution (string): 解析度，預設 '360p'。
     *   - movement_amplitude (string): 物件運動幅度 (僅 vidu1.5, vidu2.0)，預設 'auto'。
     *   - callback_url (string): 回調 URL。
     * @return TaskResponse 包含任務 ID 和初始狀態的回應物件。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果輸入參數無效。
     */
    public function referenceToVideo(string $model, array $images, string $prompt, array $options = []): TaskResponse
    {
        if (empty($prompt)) {
            throw new \InvalidArgumentException('文字提示 (prompt) 為必填項。');
        }
        if ($model === 'vidu1.0' && count($images) > 1) {
             throw new \InvalidArgumentException('vidu1.0 模型最多只接受 1 張參考圖片。');
        }
        if (($model === 'vidu1.5' || $model === 'vidu2.0') && count($images) > 3) {
             throw new \InvalidArgumentException('vidu1.5 和 vidu2.0 模型最多只接受 3 張參考圖片。');
        }
        $payload = array_merge(
            ['model' => $model, 'images' => $images, 'prompt' => $prompt],
            $options
        );
        $response = $this->client->request('POST', 'reference2video', $payload);
        return new TaskResponse($response);
    }

    /**
     * 根據開始和結束圖片生成影片 (Start end to Video)。
     *
     * @param string $model 模型名稱 (例如 'vidu2.0', 'vidu1.5')。
     * @param array $images 包含兩張圖片 (開始幀和結束幀) 的陣列。
     * @param array $options 可選參數，例如：
     *   - prompt (string): 文字提示。
     *   - duration (int): 影片時長 (秒)，預設 4。
     *   - seed (int): 隨機種子。
     *   - resolution (string): 解析度，預設 '360p'。
     *   - movement_amplitude (string): 物件運動幅度，預設 'auto'。
     *   - callback_url (string): 回調 URL。
     * @return TaskResponse 包含任務 ID 和初始狀態的回應物件。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果輸入參數無效。
     */
    public function startEndToVideo(string $model, array $images, array $options = []): TaskResponse
    {
        if (!in_array($model, ['vidu2.0', 'vidu1.5'])) {
             throw new \InvalidArgumentException('Start end to Video API 僅支援 vidu2.0 和 vidu1.5 模型。');
        }
        if (count($images) !== 2) {
            throw new \InvalidArgumentException('Start end to Video API 必須提供兩張圖片 (開始幀和結束幀)。');
        }
        $payload = array_merge(
            ['model' => $model, 'images' => $images],
            $options
        );
        $response = $this->client->request('POST', 'start-end2video', $payload);
        return new TaskResponse($response);
    }

    /**
     * 從文字生成影片 (Text to Video)。
     *
     * @param string $model 模型名稱 (例如 'vidu1.5', 'vidu1.0')。
     * @param string $prompt 文字提示。
     * @param array $options 可選參數，例如：
     *   - style (string): 影片風格 ('general', 'anime')，預設 'general'。
     *   - duration (int): 影片時長 (秒)，預設 4。
     *   - seed (int): 隨機種子。
     *   - aspect_ratio (string): 寬高比，預設 '16:9'。
     *   - resolution (string): 解析度，預設 '360p'。
     *   - movement_amplitude (string): 物件運動幅度 (僅 vidu1.5)，預設 'auto'。
     *   - callback_url (string): 回調 URL。
     * @return TaskResponse 包含任務 ID 和初始狀態的回應物件。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果輸入參數無效。
     */
    public function textToVideo(string $model, string $prompt, array $options = []): TaskResponse
    {
         if (!in_array($model, ['vidu1.5', 'vidu1.0'])) {
             throw new \InvalidArgumentException('Text to Video API 僅支援 vidu1.5 和 vidu1.0 模型。');
        }
         if (empty($prompt)) {
            throw new \InvalidArgumentException('文字提示 (prompt) 為必填項。');
        }
        $payload = array_merge(
            ['model' => $model, 'prompt' => $prompt],
            $options
        );
        $response = $this->client->request('POST', 'text2video', $payload);
        return new TaskResponse($response);
    }

    /**
     * 使用模板生成影片 (Template to Video)。
     *
     * @param string $template 模板名稱。
     * @param array $images 圖片 URL 或 Base64 編碼字串陣列 (僅限 1 張圖片)。
     * @param string $prompt 文字提示。
     * @param array $options 可選參數，例如：
     *   - seed (int): 隨機種子。
     *   - aspect_ratio (string): 寬高比，預設 '16:9' (依模板而定)。
     *   - area (string): 區域 (僅限 exotic_princess 模板)。
     *   - beast (string): 野獸類型 (僅限 beast_companion 模板)。
     *   - callback_url (string): 回調 URL。
     * @return TaskResponse 包含任務 ID 和初始狀態的回應物件。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果輸入參數無效。
     */
    public function templateToVideo(string $template, array $images, string $prompt, array $options = []): TaskResponse
    {
        if (empty($template)) {
            throw new \InvalidArgumentException('模板名稱 (template) 為必填項。');
        }
         if (count($images) !== 1) {
            throw new \InvalidArgumentException('Template to Video API 只接受一張圖片。');
        }
         if (empty($prompt)) {
            throw new \InvalidArgumentException('文字提示 (prompt) 為必填項。');
        }
        $payload = array_merge(
            ['template' => $template, 'images' => $images, 'prompt' => $prompt],
            $options
        );
        $response = $this->client->request('POST', 'template2video', $payload);
        return new TaskResponse($response);
    }

    /**
     * 提升影片解析度 (Upscale)。
     *
     * @param string $creationId 要提升解析度的影片創作 ID (從 Get Generation API 取得)。
     * @param string $model 模型名稱 (目前僅支援 'vidu1.0')。
     * @param string|null $callbackUrl 回調 URL。
     * @return TaskResponse 包含新任務 ID 和初始狀態的回應物件。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果輸入參數無效。
     */
    public function upscaleVideo(string $creationId, string $model = 'vidu1.0', ?string $callbackUrl = null): TaskResponse
    {
        if ($model !== 'vidu1.0') {
             throw new \InvalidArgumentException('Upscale API 目前僅支援 vidu1.0 模型。');
        }
        if (empty($creationId)) {
            throw new \InvalidArgumentException('創作 ID (creationId) 為必填項。');
        }
        $payload = [
            'model' => $model,
            'creation_id' => $creationId,
        ];
        if ($callbackUrl !== null) {
            $payload['callback_url'] = $callbackUrl;
        }
        $response = $this->client->request('POST', 'upscale', $payload);
        return new TaskResponse($response);
    }
}
