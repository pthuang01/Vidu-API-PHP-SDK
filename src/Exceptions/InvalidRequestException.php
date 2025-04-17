<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 無效請求例外 (HTTP 400)
 *
 * 表示請求的參數有問題，例如缺少欄位、格式錯誤、值超出範圍等。
 * 不包含額度不足的情況。
 */
class InvalidRequestException extends ViduApiException
{
    public function __construct(string $message = "無效的請求參數", int $code = 400, ?string $errorCode = null, ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $errorCode, $responseBody, $previous);
    }
}
