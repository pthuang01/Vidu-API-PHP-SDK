<?php

namespace Vidu\SDK\Services;

use Vidu\SDK\Exceptions\ViduApiException;
use Vidu\SDK\Models\CreditInfo;

/**
 * Vidu 帳戶資訊服務
 *
 * 處理與帳戶額度查詢相關的 API 端點。
 */
class AccountService extends AbstractService
{
    /**
     * 獲取帳戶的額度資訊。
     *
     * @param bool $showDetail 是否顯示所有有效套餐的詳細資訊，預設為 false。
     * @return CreditInfo 包含帳戶額度資訊的物件。
     * @throws ViduApiException 如果 API 請求失敗。
     */
    public function getCredits(bool $showDetail = false): CreditInfo
    {
        $queryParams = ['show_detail' => $showDetail ? 'true' : 'false'];
        $response = $this->client->request('GET', 'credits', null, $queryParams);
        return new CreditInfo($response);
    }
}
