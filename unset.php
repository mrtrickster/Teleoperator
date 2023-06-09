<?php
// Load composer
require_once __DIR__ . '/vendor/autoload.php';

// Load all configuration options
$config = require __DIR__ . 'config.php';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);

    // Unset / delete the webhook
    $result = $telegram->deleteWebhook();
    echo $result->getDescription();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}