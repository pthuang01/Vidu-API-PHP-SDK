<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 內部伺服器錯誤例外 (HTTP 500)
 *
 * 表示 Vidu 伺服器端發生了未預期的錯誤。
 */
class InternalServerException extends ViduApiException
{
    public function __construct(string $message = "Vidu 伺服器內部錯誤", int $code = 500, ?string $errorCode = 'InternalServiceFailure', ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $errorCode, $responseBody, $previous);
    }
}
