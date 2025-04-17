# Vidu API PHP SDK

這是一個用於與 [Vidu API](https://vidu.com/) 互動的完整 PHP 客戶端函式庫。此 SDK 簡化了將 Vidu 強大的影片生成功能（圖片轉影片、參考圖轉影片、文字轉影片、模板、畫質提升等）整合到您的 PHP 應用程式中的過程。

## 功能特色

*   涵蓋所有主要的 Vidu API v2 端點：
    *   影片生成（圖片、參考圖、開始/結束圖、文字、模板、畫質提升）
    *   任務管理（獲取生成狀態、取消生成任務）
    *   帳戶資訊（獲取額度）
    *   圖片上傳（處理三步驟的上傳流程）
*   簡潔的物件導向介面。
*   結構化的回應模型，方便存取資料。
*   針對不同 API 錯誤類型的特定例外處理（驗證、速率限制、未找到等）。
*   相容 PSR-4 自動載入標準。
*   豐富的 PHPDoc 註解，易於整合並獲得 IDE 支援。

## 環境需求

*   PHP >= 7.4
*   Composer
*   PHP cURL 擴充套件

## 安裝方式

在根目錄執行 `composer install` 即可

## 基本用法

請參考 examples\

## 可用的服務與方法

SDK 透過 `ViduClient` 實例的屬性提供對 Vidu API 不同部分的存取：

*   **`$vidu->videos` (`Vidu\SDK\Services\VideoService`)**
    *   `imageToVideo(string $model, array $images, array $options = []): TaskResponse`
    *   `referenceToVideo(string $model, array $images, string $prompt, array $options = []): TaskResponse`
    *   `startEndToVideo(string $model, array $images, array $options = []): TaskResponse`
    *   `textToVideo(string $model, string $prompt, array $options = []): TaskResponse`
    *   `templateToVideo(string $template, array $images, string $prompt, array $options = []): TaskResponse`
    *   `upscaleVideo(string $creationId, string $model = 'vidu1.0', ?string $callbackUrl = null): TaskResponse`
*   **`$vidu->tasks` (`Vidu\SDK\Services\TaskService`)**
    *   `getGeneration(string $taskId): GenerationResult`
    *   `cancelGeneration(string $taskId): array`
*   **`$vidu->account` (`Vidu\SDK\Services\AccountService`)**
    *   `getCredits(bool $showDetail = false): CreditInfo`
*   **`$vidu->files` (`Vidu\SDK\Services\FileService`)**
    *   `uploadImage(string $filePath, ?string $contentType = null, string $scene = 'vidu'): string` (處理三步驟流程)
    *   `createUploadLink(string $scene = 'vidu'): array`
    *   `uploadImageToLink(string $putUrl, string $filePath, string $contentType): string`
    *   `completeUpload(string $resourceId, string $etag): array`

請參考原始碼（特別是 `src/Services` 目錄下的類別以及 `src/Models` 中的回應物件）以獲取透過 PHPDocs 註解的詳細參數、選項和回傳類型。

## API 使用限制與注意事項

在使用 Vidu API 及此 SDK 時，請注意以下幾點：

*   **API 金鑰 (Token)**: 您的 API 金鑰是機密資訊，請妥善保管，切勿直接寫在程式碼或提交到版本控制系統中。建議使用環境變數或其他安全的配置管理方式。
*   **額度 (Credits)**: 大部分的 API 呼叫（特別是影片生成）會消耗您帳戶中的額度。請透過 `$vidu->account->getCredits()` 或 Vidu 官方平台監控您的額度餘額。額度不足會導致 API 呼叫失敗 (`InsufficientCreditsException`)。
*   **速率限制 (Rate Limiting)**: Vidu API 可能會對請求頻率和併發數量設有​​限制。如果您的請求過於頻繁或同時執行的任務過多，可能會收到 429 錯誤 (`RateLimitExceededException`)。請根據您的帳戶類型和 Vidu 的規範調整您的請求策略，考慮使用佇列或延遲重試機制。
*   **任務處理時間**: 影片生成任務需要一定的處理時間，從幾十秒到幾分鐘不等，具體取決於模型、參數和伺服器負載。請勿假設任務會立即完成。
*   **結果獲取**: 您可以透過輪詢 `getGeneration()` 方法來檢查任務狀態，或者使用 `callback_url` 參數讓 Vidu 在任務完成時主動通知您的伺服器（推薦用於生產環境）。
*   **結果 URL 時效性**: `getGeneration()` 或回調函數返回的影片/封面 URL (`url`, `cover_url`) 通常具有時效性（例如一小時）。請在獲取後及時下載或處理，不要依賴這些 URL 的長期有效性。
*   **輸入參數限制**: 請仔細閱讀 Vidu API 文件中關於各個端點的參數要求，例如：
    *   圖片格式、尺寸、長寬比、檔案大小限制。
    *   不同模型支援的解析度、時長、風格等選項。
    *   特定模板的可用性及所需參數。
    *   文字提示 (Prompt) 的最大長度限制。
    *   提供無效的參數可能會導致 400 錯誤 (`InvalidRequestException`)。
*   **圖片來源**: 您可以提供公開可訪問的圖片 URL，或使用 SDK 提供的 `uploadImage()` 方法先將本地圖片上傳到 Vidu（推薦，可以避免因 URL 無法訪問導致的 `ImageDownloadFailure` 錯誤）。上傳後的 URI (`ssupload:?id=...`) 可用於 API 呼叫。
*   **內容政策**: 請遵守 Vidu 的內容政策，避免生成違規或不當內容。違反政策的請求可能會導致 `AuditSubmitIllegal` 或 `CreationPolicyViolation` 錯誤。
*   **錯誤處理**: 強烈建議在您的應用程式中實作完善的錯誤處理邏輯，捕捉 SDK 拋出的特定例外，並根據錯誤類型採取適當的行動（例如重試、記錄錯誤、通知用戶等）。

**建議**: 在將應用程式部署到生產環境之前，請務必在測試環境中充分測試各種 API 呼叫和錯誤情況。

## 錯誤處理

SDK 使用例外（Exception）來表示錯誤。所有 API 特定的錯誤都繼承自基礎的 `Vidu\SDK\Exceptions\ViduApiException`。

您可以捕捉基礎例外：

```php
try {
    // ... API 呼叫 ...
} catch (Vidu\SDK\Exceptions\ViduApiException $e) {
    // 處理一般的 API 錯誤
    echo "API 錯誤碼: " . $e->getErrorCode() . "
";
    echo "訊息: " . $e->getMessage() . "
";
}
```

或者捕捉更特定的例外（建議）以進行更精細的控制：

*   `Vidu\SDK\Exceptions\AuthenticationException` (401 Unauthorized)
*   `Vidu\SDK\Exceptions\PermissionDeniedException` (403 Forbidden)
*   `Vidu\SDK\Exceptions\NotFoundException` (404 Not Found - 例如 任務/創作 ID)
*   `Vidu\SDK\Exceptions\RateLimitExceededException` (429 Too Many Requests / Quota Exceeded)
*   `Vidu\SDK\Exceptions\InvalidRequestException` (400 Bad Request - 一般的參數錯誤)
*   `Vidu\SDK\Exceptions\InsufficientCreditsException` (400 Bad Request - 特定的 `CreditInsufficient` 錯誤碼)
*   `Vidu\SDK\Exceptions\InternalServerException` (500 Internal Server Error)

請參閱上方的「基本用法」範例，其中演示了如何捕捉多個特定的例外。

基礎的 `ViduApiException` 提供了如 `getCode()` (HTTP 狀態碼)、`getErrorCode()` (API 特定的字串錯誤碼，如果有的話) 和 `getResponseBody()` (來自 API 錯誤回應的原始解碼陣列，如果有的話) 等方法。

## 貢獻

歡迎任何貢獻！請隨時在 GitHub 儲存庫提交 Pull Request 或開啟 Issue。

## 授權條款

此 SDK 採用 MIT 授權條款
