<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 額度不足例外 (HTTP 400, ErrorCode: CreditInsufficient)
 */
class InsufficientCreditsException extends ViduApiException // 直接繼承基礎例外，因為錯誤碼是特定判斷依據
{
    public function __construct(string $message = "帳戶額度不足", int $code = 400, ?string $errorCode = 'CreditInsufficient', ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $errorCode, $responseBody, $previous);
    }
}
