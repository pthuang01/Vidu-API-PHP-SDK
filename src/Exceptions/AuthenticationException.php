<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 驗證失敗例外 (HTTP 401)
 *
 * 通常表示 API 金鑰無效或未提供。
 */
class AuthenticationException extends ViduApiException
{
    public function __construct(string $message = "API 驗證失敗", int $code = 401, ?string $errorCode = 'Unauthorized', ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $errorCode, $responseBody, $previous);
    }
}
