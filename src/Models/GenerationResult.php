<?php

namespace Vidu\SDK\Models;

/**
 * 獲取生成結果 API 的回應模型
 */
class GenerationResult
{
    /** @var string 任務狀態 (created, queueing, processing, success, failed) */
    public $state;

    /** @var string|null 錯誤碼 (如果 state 為 failed) */
    public $errCode;

    /** @var array 生成的創作列表 */
    public $creations = [];

    /** @var array 原始 API 回應數據 */
    public $rawData;

    /**
     * GenerationResult 建構子。
     *
     * @param array $data API 回應的關聯陣列。
     */
    public function __construct(array $data)
    {
        $this->state = $data['state'] ?? null;
        $this->errCode = $data['err_code'] ?? null;
        
        if (isset($data['creations']) && is_array($data['creations'])) {
            foreach ($data['creations'] as $creationData) {
                $this->creations[] = new CreationItem($creationData);
            }
        }
        
        $this->rawData = $data;

        if (empty($this->state)) {
            throw new \InvalidArgumentException('API 回應中缺少 state。');
        }
    }

    /**
     * 檢查任務是否成功完成。
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->state === 'success';
    }

    /**
     * 檢查任務是否正在處理中 (排隊或處理中)。
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return in_array($this->state, ['created', 'queueing', 'processing']);
    }

    /**
     * 檢查任務是否失敗。
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->state === 'failed';
    }
}

/**
 * 單個創作項目模型
 */
class CreationItem
{
    /** @var string 創作 ID */
    public $id;

    /** @var string|null 生成結果的 URL (有效期一小時) */
    public $url;

    /** @var string|null 生成結果封面的 URL (有效期一小時) */
    public $coverUrl;

    /**
     * CreationItem 建構子。
     *
     * @param array $data 單個 creation 的數據。
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->url = $data['url'] ?? null;
        $this->coverUrl = $data['cover_url'] ?? null;
    }
}
