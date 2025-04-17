<?php

require __DIR__ . '/../vendor/autoload.php'; // 確保您已經執行 composer install

use Vidu\SDK\ViduClient;
use Vidu\SDK\Exceptions\ViduApiException;

// 請替換為您的實際 API 金鑰
$apiKey = getenv('VIDU_API_KEY') ?: 'YOUR_API_KEY'; 

if ($apiKey === 'YOUR_API_KEY') {
    echo "錯誤：請設定 VIDU_API_KEY 環境變數或直接在程式碼中替換 YOUR_API_KEY。
";
    exit(1);
}

// 初始化 Vidu 客戶端
$vidu = new ViduClient($apiKey);

echo "Vidu SDK 初始化完成。
";

try {
    // 範例 1: 獲取帳戶額度資訊
    echo "
--- 正在獲取帳戶額度資訊 ---
";
    $creditInfo = $vidu->account->getCredits(true); // 設定為 true 以獲取詳細套餐資訊
    echo "帳戶額度資訊: 
";
    print_r($creditInfo->rawData); // 直接輸出原始數據以供參考
    echo "Metered 類型剩餘額度: " . ($creditInfo->getRemainingCreditsByType('metered') ?? 'N/A') . "
";

    // 範例 2: 上傳圖片 (如果需要)
    echo "
--- 正在上傳圖片 ---";
    $imagePath = 'path/to/your/image.png'; // 替換為您的圖片路徑
    if (file_exists($imagePath)) {
        $imageUri = $vidu->files->uploadImage($imagePath);
        echo "圖片上傳成功，URI: {$imageUri}
";
    } else {
        echo "圖片檔案 {$imagePath} 不存在，跳過上傳範例。
";
        $imageUri = 'https://prod-ss-images.s3.cn-northwest-1.amazonaws.com.cn/vidu-maas/template/image2video.png'; // 使用範例 URL
    }

    // 範例 3: 使用圖片 URL 觸發 Image to Video
    echo "
--- 正在觸發 Image to Video 任務 ---
";
    $imageUrl = 'https://prod-ss-images.s3.cn-northwest-1.amazonaws.com.cn/vidu-maas/template/image2video.png';
    $img2VideoOptions = [
        'prompt' => '太空人揮手，鏡頭向上移動。',
        'duration' => 4,
        'resolution' => '720p',
        // 'callback_url' => 'https://your.domain/vidu_callback' // 可選的回調 URL
    ];
    $taskResponse = $vidu->videos->imageToVideo('vidu2.0', [$imageUrl], $img2VideoOptions);
    echo "Image to Video 任務已創建，任務 ID: {$taskResponse->taskId}, 狀態: {$taskResponse->state}
";
    $taskId = $taskResponse->taskId;

    // 範例 4: 輪詢檢查任務狀態
    echo "
--- 正在輪詢任務狀態 (最多等待 60 秒) ---
";
    $startTime = time();
    $timeout = 60; // 秒
    $generationResult = null;
    while (time() - $startTime < $timeout) {
        echo "檢查任務 {$taskId} 狀態...
";
        $generationResult = $vidu->tasks->getGeneration($taskId);
        echo "當前狀態: {$generationResult->state}
";

        if ($generationResult->isSuccess()) {
            echo "任務成功完成！
";
            break;
        }
        if ($generationResult->isFailed()) {
            echo "任務失敗。錯誤碼: {$generationResult->errCode}
";
            break;
        }
        if (!$generationResult->isProcessing()) {
            echo "任務處於非預期狀態: {$generationResult->state}
";
            break;
        }

        sleep(5); // 等待 5 秒後再次檢查
    }

    if ($generationResult && $generationResult->isSuccess()) {
        echo "
--- 獲取生成結果 ---
";
        foreach ($generationResult->creations as $creation) {
            echo "創作 ID: {$creation->id}
";
            echo "影片 URL: {$creation->url}
";
            echo "封面 URL: {$creation->coverUrl}
";
            // 在這裡您可以下載影片或進行其他處理

            // 範例 5: (可選) 觸發 Upscale (如果原始解析度是 360p 且模型支援)
            // if ($taskResponse->resolution === '360p' && $taskResponse->identifier === 'vidu1.0') { // 假設原始任務是 vidu1.0 且 360p
            //     echo "
// --- 正在觸發 Upscale 任務 ---";
            //     $upscaleResponse = $vidu->videos->upscaleVideo($creation->id, 'vidu1.0');
            //     echo "Upscale 任務已創建，任務 ID: {$upscaleResponse->taskId}, 狀態: {$upscaleResponse->state}
// ";
            //     // 您可以像上面一樣輪詢這個新的 upscale 任務 ID
            // }
        }
    } elseif (!$generationResult || $generationResult->isProcessing()) {
        echo "
任務超時未完成。任務 ID: {$taskId}
";
        // 範例 6: (可選) 取消超時的任務
        // echo "
// --- 嘗試取消任務 {$taskId} ---";
        // try {
        //     $cancelResponse = $vidu->tasks->cancelGeneration($taskId);
        //     echo "取消請求已發送。API 回應: ";
        //     print_r($cancelResponse);
        // } catch (ViduApiException $e) {
        //     echo "取消任務時出錯: " . $e->getMessage() . "
// ";
        // }
    }

    // 您可以在此處加入更多範例，例如 Text to Video, Reference to Video 等。

} catch (AuthenticationException $e) {
    echo "\\n!!! 驗證錯誤 !!!\\n";
    echo "請檢查您的 API 金鑰是否正確且有效。\n";
    echo "訊息: " . $e->getMessage() . "\\n";
} catch (InsufficientCreditsException $e) {
    echo "\\n!!! 額度不足錯誤 !!!\\n";
    echo "您的 Vidu 帳戶額度不足以完成此操作。\n";
    echo "訊息: " . $e->getMessage() . "\\n";
} catch (RateLimitExceededException $e) {
    echo "\\n!!! 速率限制錯誤 !!!\\n";
    echo "請求過於頻繁或超出了帳戶的併發限制，請稍後再試。\n";
    echo "訊息: " . $e->getMessage() . "\\n";
     if ($e->getErrorCode()) {
        echo "API 錯誤碼: " . $e->getErrorCode() . "\\n"; // 例如 QuotaExceeded
    }
} catch (NotFoundException $e) {
    echo "\\n!!! 資源未找到錯誤 !!!\\n";
    echo "請求的資源 (例如任務 ID 或創作 ID) 不存在。\n";
    echo "訊息: " . $e->getMessage() . "\\n";
} catch (InvalidRequestException $e) {
    // 捕捉所有其他的 400 錯誤 (非額度不足)
    echo "\\n!!! 無效請求錯誤 !!!\\n";
    echo "請求參數有誤，請檢查 API 文件和您的輸入。\n";
    echo "訊息: " . $e->getMessage() . "\\n";
    if ($e->getErrorCode()) {
        echo "API 錯誤碼: " . $e->getErrorCode() . "\\n";
    }
    if ($e->getResponseBody()) {
        echo "API 回應 Body: \\n";
        print_r($e->getResponseBody());
    }
} catch (PermissionDeniedException $e) {
     echo "\\n!!! 權限錯誤 !!!\\n";
     echo "您沒有權限執行此操作。\n";
     echo "訊息: " . $e->getMessage() . "\\n";
} catch (InternalServerException $e) {
     echo "\\n!!! 伺服器內部錯誤 !!!\\n";
     echo "Vidu 伺服器遇到問題，請稍後再試或聯繫支援。\n";
     echo "訊息: " . $e->getMessage() . "\\n";
} catch (ViduApiException $e) {
    // 捕捉所有其他未被特定捕捉的 Vidu API 錯誤
    echo "\\n!!! 其他 Vidu API 錯誤 !!!\\n";
    echo "訊息: " . $e->getMessage() . "\\n";
    echo "HTTP 狀態碼: " . $e->getCode() . "\\n";
    if ($e->getErrorCode()) {
        echo "API 錯誤碼: " . $e->getErrorCode() . "\\n";
    }
    if ($e->getResponseBody()) {
        echo "API 回應 Body: \\n";
        print_r($e->getResponseBody());
    }
} catch (\\InvalidArgumentException $e) {
    // 處理 SDK 內部的無效參數錯誤 (例如 Client __construct 的 API Key 為空)
    echo "\\n!!! 無效參數錯誤 (SDK) !!!\\n";
    echo "訊息: " . $e->getMessage() . "\\n";
} catch (\\Exception $e) {
    // 處理其他一般錯誤 (例如 cURL 連線問題 - 雖然已被 ViduApiException 捕捉，但保留以防萬一)
    echo "\\n!!! 一般錯誤 !!!\\n";
    echo "訊息: " . $e->getMessage() . "\\n";
    echo "檔案: " . $e->getFile() . " 行: " . $e->getLine() . "\\n";
}
