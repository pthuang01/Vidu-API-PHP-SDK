<?php

namespace Vidu\SDK\Models;

/**
 * API 任務創建回應模型
 *
 * 用於封裝啟動生成任務時 API 的初始回應。
 */
class TaskResponse
{
    /** @var string 任務 ID */
    public $taskId;

    /** @var string 任務初始狀態 */
    public $state;

    /** @var string 使用的模型/模板 */
    public $identifier;

    /** @var array 請求中使用的圖片 */
    public $images;

    /** @var string|null 請求中使用的文字提示 */
    public $prompt;

    /** @var int|null 請求中使用的影片時長 */
    public $duration;

    /** @var int|null 請求中使用的隨機種子 */
    public $seed;

    /** @var string|null 請求中使用的解析度 */
    public $resolution;

    /** @var string|null 請求中使用的寬高比 */
    public $aspectRatio;

    /** @var string|null 請求中使用的運動幅度 */
    public $movementAmplitude;

     /** @var string|null 請求中使用的風格 */
    public $style;

    /** @var string|null 請求中使用的 creation_id (針對 upscale) */
    public $creationId;

    /** @var string 任務創建時間 */
    public $createdAt;

    /** @var array 原始 API 回應數據 */
    public $rawData;

    /**
     * TaskResponse 建構子。
     *
     * @param array $data API 回應的關聯陣列。
     */
    public function __construct(array $data)
    {
        $this->taskId = $data['task_id'] ?? null;
        $this->state = $data['state'] ?? null;
        $this->identifier = $data['model'] ?? ($data['template'] ?? null);
        $this->images = $data['images'] ?? null;
        $this->prompt = $data['prompt'] ?? null;
        $this->duration = isset($data['duration']) ? (int)$data['duration'] : null;
        $this->seed = isset($data['seed']) ? (int)$data['seed'] : null;
        $this->resolution = $data['resolution'] ?? null;
        $this->aspectRatio = $data['aspect_ratio'] ?? null;
        $this->movementAmplitude = $data['movement_amplitude'] ?? null;
        $this->style = $data['style'] ?? null;
        $this->creationId = $data['creation_id'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->rawData = $data;

        if (empty($this->taskId)) {
            // 如果 task_id 為空，可能是一個錯誤的回應，雖然 Client 層應該先攔截
            // 但在此加一層防護
            throw new \InvalidArgumentException('API 回應中缺少 task_id。');
        }
    }
}
