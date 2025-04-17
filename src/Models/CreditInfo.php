<?php

namespace Vidu\SDK\Models;

/**
 * 帳戶額度資訊回應模型
 */
class CreditInfo
{
    /** @var array 剩餘額度摘要列表 */
    public $remains = [];

    /** @var array 套餐詳細資訊列表 (如果 show_detail 為 true) */
    public $packages = [];

    /** @var array 原始 API 回應數據 */
    public $rawData;

    /**
     * CreditInfo 建構子。
     *
     * @param array $data API 回應的關聯陣列。
     */
    public function __construct(array $data)
    {
        if (isset($data['remains']) && is_array($data['remains'])) {
            foreach ($data['remains'] as $remainData) {
                $this->remains[] = new CreditRemainItem($remainData);
            }
        }

        if (isset($data['packages']) && is_array($data['packages'])) {
            foreach ($data['packages'] as $packageData) {
                $this->packages[] = new PackageDetailItem($packageData);
            }
        }
        
        $this->rawData = $data;

        // 基本驗證，確保至少有 remains
        if (empty($this->remains)) {
            // 注意：API 在沒有任何額度時可能回傳空陣列，所以不拋出例外
            // 但若連 remains 欄位都沒有，則可能是有問題
            if (!isset($data['remains'])) {
                 throw new \InvalidArgumentException('API 回應中缺少 remains 欄位。');
            }
        }
    }

    /**
     * 獲取特定類型的總剩餘額度。
     *
     * @param string $type 套餐類型 ('test', 'metered', 'concurrent')。
     * @return int|null 如果找到則返回剩餘額度，否則返回 null。
     */
    public function getRemainingCreditsByType(string $type): ?int
    {
        foreach ($this->remains as $remain) {
            if ($remain->type === $type) {
                return $remain->creditRemain;
            }
        }
        return null;
    }

     /**
     * 獲取特定類型的最大併發數。
     *
     * @param string $type 套餐類型 ('test', 'metered', 'concurrent')。
     * @return int|null 如果找到則返回最大併發數，否則返回 null。
     */
    public function getConcurrencyLimitByType(string $type): ?int
    {
        foreach ($this->remains as $remain) {
            if ($remain->type === $type) {
                return $remain->concurrencyLimit;
            }
        }
        return null;
    }

     /**
     * 獲取特定類型的當前使用併發數。
     *
     * @param string $type 套餐類型 ('test', 'metered', 'concurrent')。
     * @return int|null 如果找到則返回當前使用併發數，否則返回 null。
     */
    public function getCurrentConcurrencyByType(string $type): ?int
    {
        foreach ($this->remains as $remain) {
            if ($remain->type === $type) {
                return $remain->currentConcurrency;
            }
        }
        return null;
    }
}

/**
 * 剩餘額度摘要項目模型
 */
class CreditRemainItem
{
    /** @var string 套餐類型 ('test', 'metered', 'concurrent') */
    public $type;

    /** @var int 此類型套餐的總剩餘額度 */
    public $creditRemain;

    /** @var int 最大併發使用限制 */
    public $concurrencyLimit;

    /** @var int 當前已使用的併發數 */
    public $currentConcurrency;

    /**
     * CreditRemainItem 建構子。
     *
     * @param array $data 單個 remain 的數據。
     */
    public function __construct(array $data)
    {
        $this->type = $data['type'] ?? null;
        $this->creditRemain = isset($data['credit_remain']) ? (int)$data['credit_remain'] : null;
        $this->concurrencyLimit = isset($data['concurrency_limit']) ? (int)$data['concurrency_limit'] : null;
        $this->currentConcurrency = isset($data['current_concurrency']) ? (int)$data['current_concurrency'] : null;
    }
}

/**
 * 套餐詳細資訊項目模型
 */
class PackageDetailItem
{
    /** @var string 套餐 ID */
    public $id;

    /** @var string 套餐名稱 */
    public $name;

    /** @var string 套餐類型 ('test', 'metered', 'concurrent') */
    public $type;

    /** @var int 併發數 */
    public $concurrency;

    /** @var int 總額度 */
    public $creditAmount;

    /** @var int 剩餘額度 */
    public $creditRemain;

    /** @var string 訂單時間 */
    public $createdAt;

    /** @var string 購買時間 */
    public $purchaseAt;

    /** @var string 生效時間 */
    public $validFrom;

    /** @var string 到期時間 */
    public $validTo;

    /**
     * PackageDetailItem 建構子。
     *
     * @param array $data 單個 package 的數據。
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->concurrency = isset($data['concurrency']) ? (int)$data['concurrency'] : null;
        $this->creditAmount = isset($data['credit_amount']) ? (int)$data['credit_amount'] : null;
        $this->creditRemain = isset($data['credit_remain']) ? (int)$data['credit_remain'] : null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->purchaseAt = $data['purchase_at'] ?? null;
        $this->validFrom = $data['valid_from'] ?? null;
        $this->validTo = $data['valid_to'] ?? null;
    }
}
