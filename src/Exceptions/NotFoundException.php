<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 資源未找到例外 (HTTP 404)
 *
 * 例如，查詢不存在的任務 ID 或創作 ID。
 */
class NotFoundException extends ViduApiException
{
    public function __construct(string $message = "請求的資源未找到", int $code = 404, ?string $errorCode = null, ?array $responseBody = null, ?\Throwable $previous = null)
    {
        // 404 可能對應不同的 errorCode (TaskNotFound, CreationNotFound)
        parent::__construct($message, $code, $errorCode, $responseBody, $previous);
    }
}
