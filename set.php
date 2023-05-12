<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';

// Load all configuration options
$config = require __DIR__ . 'config.php';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);

    // Set webhook
    $result = $telegram->setWebhook($config['webhook']['url']);
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    // echo $e->getMessage();
}