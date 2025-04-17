<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 權限不足例外 (HTTP 403)
 *
 * 表示請求的操作被禁止。
 */
class PermissionDeniedException extends ViduApiException
{
    public function __construct(string $message = "權限不足，請求被禁止", int $code = 403, ?string $errorCode = 'Forbidden', ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $errorCode, $responseBody, $previous);
    }
}
