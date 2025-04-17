<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 速率限制超出例外 (HTTP 429)
 *
 * 表示請求過於頻繁或超出併發限制。
 */
class RateLimitExceededException extends ViduApiException
{
    public function __construct(string $message = "請求過於頻繁或超出限制", int $code = 429, ?string $errorCode = null, ?array $responseBody = null, ?\Throwable $previous = null)
    {
        // 429 可能對應 QuotaExceeded, TooManyRequests, SystemThrottling
        parent::__construct($message, $code, $errorCode, $responseBody, $previous);
    }
}
