<?php

namespace Vidu\SDK\Exceptions;

/**
 * Vidu API 例外類別
 *
 * 用於表示與 Vidu API 交互時發生的錯誤。
 */
class ViduApiException extends \Exception
{
    /**
     * API 回應的錯誤碼 (如果可用)
     * @var string|null
     */
    protected $errorCode;

    /**
     * API 回應的完整內容 (如果可用)
     * @var array|null
     */
    protected $responseBody;

    /**
     * ViduApiException 建構子。
     *
     * @param string $message 例外訊息。
     * @param int $code HTTP 狀態碼。
     * @param string|null $errorCode API 特定的錯誤碼。
     * @param array|null $responseBody API 回應的內容。
     * @param \Throwable|null $previous 先前的例外。
     */
    public function __construct(string $message = "", int $code = 0, ?string $errorCode = null, ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->responseBody = $responseBody;
    }

    /**
     * 取得 API 錯誤碼。
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * 取得 API 回應內容。
     *
     * @return array|null
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
