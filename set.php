<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';

$bot_api_key  = '5966059872:AAHta4EER_xeCpisYrpDaxFWQMnTsAzLhWk';
$bot_username = 'TeleoperatorBot';
$hook_url     = 'https://livebot.me/bots/Teleoperator/v01/hook.php';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Set webhook
    $result = $telegram->setWebhook($hook_url);
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    // echo $e->getMessage();
}