<?php

namespace Vidu\SDK\Services;

use Vidu\SDK\Exceptions\ViduApiException;
use Vidu\SDK\Models\GenerationResult;

/**
 * Vidu 任務管理服務
 *
 * 處理與任務狀態查詢、取消等相關的 API 端點。
 */
class TaskService extends AbstractService
{
    /**
     * 獲取指定任務的生成結果。
     *
     * @param string $taskId 任務 ID。
     * @return GenerationResult 包含任務狀態和生成結果的物件。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果任務 ID 為空。
     */
    public function getGeneration(string $taskId): GenerationResult
    {
        if (empty($taskId)) {
            throw new \InvalidArgumentException('任務 ID (taskId) 為必填項。');
        }
        $endpoint = "tasks/{$taskId}/creations";
        $response = $this->client->request('GET', $endpoint);
        return new GenerationResult($response);
    }

    /**
     * 取消指定的任務。
     *
     * @param string $taskId 任務 ID。
     * @return array API 回應 (通常是空的成功回應或錯誤訊息)。
     * @throws ViduApiException 如果 API 請求失敗。
     * @throws \InvalidArgumentException 如果任務 ID 為空。
     */
    public function cancelGeneration(string $taskId): array
    {
        if (empty($taskId)) {
            throw new \InvalidArgumentException('任務 ID (taskId) 為必填項。');
        }
        // 文件說明請求 body 需要包含 id，即使它也在 URL 中
        $payload = ['id' => $taskId]; 
        $endpoint = "tasks/{$taskId}/cancel";
        // 取消操作可能回傳 200 OK 但沒有 body，或是有錯誤訊息
        return $this->client->request('POST', $endpoint, $payload); 
    }
}
