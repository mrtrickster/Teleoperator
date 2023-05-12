<?php
//ini_set("log_errors", 1);
//ini_set("error_log", "./tg.log");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load composer
require_once __DIR__ . '/vendor/autoload.php';

// Load all configuration options
$config = require __DIR__ . '/config.php';

use Longman\TelegramBot\Request;
use Exception;
use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Exception\TelegramException;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

$pdo = new PDO('mysql:host=' . $config['mysql']['host'] . ';dbname=' . $config['mysql']['database'], $config['mysql']['user'], $config['mysql']['password']);

const IDLE = 0;
const MENU = 1;
const REGISTRATION = 2;
const REG_STEP1 = 3;
const REG_STEP2 = 4;
const REG_STEP3 = 5;
const REG_SUCCESS = 6;
const REGISTERED = 7;

$file = '';
$user_id = 0;
$user_telegram_id = 0;
$user_realname = '';
$user_email = '';
$user_phone = '';


class User {
    public $pdo;

    public $state = 0;
    public $user_id;
    public $telegram_name;
    public $first_name;
    public $email;
    public $phone;
    
    public function __construct() {
        echo "User created";
    }

    public function set_state($new_state) {
        $this->state = $new_state;
        switch ($this->state) {
            
        }
    }

    public function greeting() {

    }
    
    public function registration() {
        global $file;
        global $m_c_Id;
        global $m_Text;
        global $user_realname;
        global $user_email;
        global $user_phone;
        Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Приступаем к регистрации: ". $this->state]);
        switch ($this->state) {
            case REGISTRATION:
                $this->state = REG_STEP1;
                if (isset($file)) {
                    file_put_contents($file, $this->state);
                }
                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Для начала регистрации введите своё имя:"]);
                break;
            case REG_STEP1:
                $user_realname = $m_Text;
                $this->state = REG_STEP2;
                if (isset($file)) {
                    file_put_contents($file, $this->state);
                }
                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Приятно познакомиться, " . $user_realname . "! Теперь введите ваш адрес электронной почты:"]);
                break;
            case REG_STEP2:
                $user_email = $m_Text;
                $this->state = REG_STEP3;
                if (isset($file)) {
                    file_put_contents($file, $this->state);
                }
                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Ваш адрес электронной почты: " . $user_email . "! Для окончания регистрации введите ваш телефонный номер:"]);
                break;
            case REG_STEP3:
                $user_phone = $m_Text;
                $this->state = REG_SUCCESS;
                if (isset($file)) {
                    file_put_contents($file, $this->state);
                }
                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Ваш номер телефона: " . $user_phone . "! Спасибо за регистрацию, теперь вам доступен Личный кабинет."]);
                break;
        }
    }

    //

    public function if_user_exist() {
        // Check if user exists in the database
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE telegram_id = :m_From_Id');
        $stmt->bindParam(':m_From_Id', $m_From_Id);
        $stmt->execute();
        return($stmt->fetchColumn() > 0);
    }
}

$User = new User();

try {
    $telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);

    echo "Telegram client created";

    Longman\TelegramBot\TelegramLog::initErrorLog(__DIR__ . "/{$config['bot_username']}_error.log");
    Longman\TelegramBot\TelegramLog::initDebugLog(__DIR__ . "/{$config['bot_username']}_debug.log");
    Longman\TelegramBot\TelegramLog::initUpdateLog(__DIR__ . "/{$config['bot_username']}_update.log");

    $telegram->enableLimiter();
    
    $post = json_decode(Request::getInput(), true);
    if (isset($post)) {
        $update = new Update($post, $config['bot_username']);
        
        if (isset($update)) {
            $message = $update->getMessage();
            if (isset($message)) {
                $m_Id = $message->getMessageId();
                $m_ThreadId = $message->getMessageThreadId();
                $m_From = $message->getFrom();
                if (isset($m_From)) {
                    $m_From_Id = $m_From->getId();
                    if (isset($m_From_Id)) {
                        $user_telegram_id = $m_From_Id;
                        $file = 'users/' . $user_telegram_id . '.txt';
                        $file_content = file_get_contents($file);
                        if (isset($file_content)) {
                            $User->state = $file_content;
                        }
                    }
                    $m_From_IsBot = $m_From->getIsBot();
                    $m_From_FirstName = $m_From->getFirstName();
                    $m_From_LastName = $m_From->getLastName();
                    $m_From_Username = $m_From->getUsername();
                    $m_From_LanguageCode = $m_From->getLanguageCode();
                    $m_From_IsPremium = $m_From->getIsPremium();
                    $m_From_AddedToAttachmentMenu = $m_From->getAddedToAttachmentMenu();
                    $m_From_CanJoinGroups = $m_From->getCanJoinGroups();
                    $m_From_CanReadAllGroupMessages = $m_From->getCanReadAllGroupMessages();
                    $m_From_SupportsInlineQueries = $m_From->getSupportsInlineQueries();
                }
                $m_SenderChat = $message->getSenderChat();
                $m_Date = $message->getDate();
                $m_Chat = $message->getChat();
                if (isset($m_Chat)) {
                    $m_c_Id = $m_Chat->getId();
                    if (isset($m_c_Id)) {
                        Request::sendMessage(['chat_id' => $m_c_Id, 'text' => json_encode($update, JSON_PRETTY_PRINT) . "\n\ncurrent state: " . $User->state]);
                    }
                }
                $m_Text = $message->getText();
                if (isset($m_Chat) && isset($m_Text)) {
                    $User->registration();
                }
                $m_ForwardFrom = $message->getForwardFrom();
                $m_ForwardFromChat = $message->getForwardFromChat();
                $m_ForwardFromMessageId = $message->getForwardFromMessageId();
                $m_ForwardSignature = $message->getForwardSignature();
                $m_ForwardSenderName = $message->getForwardSenderName();
                $m_ForwardDate = $message->getForwardDate();
                $m_IsTopicMessage = $message->getIsTopicMessage();
                $m_IsAutomaticForward = $message->getIsAutomaticForward();
                $m_ReplyToMessage = $message->getReplyToMessage();
                $m_ViaBot = $message->getViaBot();
                $m_EditDate = $message->getEditDate();
                $m_HasProtectedContent = $message->getHasProtectedContent();
                $m_MediaGroupId = $message->getMediaGroupId();
                $m_AuthorSignature = $message->getAuthorSignature();
                $m_Entities = $message->getEntities();
                if (isset($m_Entities)) {
                    foreach ($m_Entities as $m_Entity) {
                        // string
                        // Type of the entity
                        $m_e_Type = $m_Entity->getType();
                        if (isset($m_e_Type)) {
                            switch ($m_e_Type) {
                                case "mention":
                                    // @username
                                    break;
                                case "hashtag":
                                    // #hashtag
                                    break;
                                case "cashtag":
                                    // $USD
                                    break;
                                case "bot_command":
                                    // /start@jobs_bot
                                    break;
                                case "url":
                                    // https://telegram.org
                                    break;
                                case "email":
                                    // do-not-reply@telegram.org
                                    break;
                                case "phone_number":
                                    // +1-212-555-0123
                                    break;
                                case "bold":
                                    // bold text
                                    break;
                                case "italic":
                                    // italic text
                                    break;
                                case "underline":
                                    // underlined text
                                    break;
                                case "strikethrough":
                                    // strikethrough text
                                    break;
                                case "spoiler":
                                    // spoiler message
                                    break;
                                case "code":
                                    // monowidth string
                                    break;
                                case "pre":
                                    // monowidth block
                                    break;
                                case "text_link":
                                    // for clickable text URLs
                                    break;
                                case "text_mention":
                                    // for users without usernames
                                    break;
                                case "custom_emoji":
                                    // for inline custom emoji stickers
                                    break;        
                            }
                        }
                        
                        // int
                        // Offset in UTF-16 code units to the start of the entity
                        $m_e_Offset = $m_Entity->getOffset();
                        
                        // int
                        // Length of the entity in UTF-16 code units
                        $m_e_Length = $m_Entity->getLength();
                        
                        // string, optional
                        // For "text_link" only, url that will be opened after user taps on the text
                        $m_e_Url = $m_Entity->getUrl();
                        
                        // User, optional
                        // for "text_mention" only, the mentioned user
                        $m_e_User = $m_Entity->getUser();
                        
                        // string, optional
                        // for "pre" only, the programming language of the entity text
                        $m_e_Language = $m_Entity->getLanguage();
                        
                        // string, optional
                        // for “custom_emoji” only, unique identifier of the custom emoji
                        // use getCustomEmojiStickers to get full information about the sticker
                        $m_e_CustomEmojiId = $m_Entity->getCustomEmojiId();
                        
                        if (isset($m_Text) && isset($m_e_Type) && isset($m_e_Offset) && isset($m_e_Length)) {
                            $m_e_Text = mb_substr($m_Text, $m_e_Offset, $m_e_Length, 'UTF-8');
                            
                            if (isset($m_c_Id) && isset($m_e_Text)) {
                                switch ($m_e_Type) {
                                    case "bot_command":
                                
                                        switch ($m_e_Text) {
                                            case "/start":
                                                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Добро пожаловать в Bot Studio!\nПредлагаем вашему вниманию коллекцию telegram-ботов на все случаи жизни.\nДля просмотра каталога ботов нажмите /catalog"]);
                                                if (isset($m_From_Id)) {
                                                    if ($User->if_user_exist()) {
                                                        Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "User already exists in the database."]);
                                                    } else {
                                                        Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "User not found in the database."]);
                                                    }
                                                }
                                                break;
                                            case "/menu":
                                                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Выберите интересующий вас пункт меню:\n- Регистрация /registration\n- Каталог ботов /catalog\n- Инструкция /help"]);
                                                break;
                                            case "/registration":
                                                $User->state = REGISTRATION;
                                                $User->registration();
                                                break;
                                            case "/help":
                                                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Инструкция."]);
                                                break;
                                            case "/catalog":
                                                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Каталог ботов Bot Studio.\nВыберите интересующую категорию:\n- Персональные боты /personal_bots\n- Боты для бизнеса /business_bots\n- Боты для чатов и каналов /channel_bots\n\nДля возврата в меню нажмите /menu\nДля получения инструкции нажмите /help"]);
                                                break;
                                            default:
                                                Request::sendMessage(['chat_id' => $m_c_Id, 'text' => "Команда " . $m_e_Text . " не существует."]);
                                                break;
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
                $m_CaptionEntities = $message->getCaptionEntities();
            }
        }
    }
    
    return Request::emptyResponse();
    
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    echo $e;
    // Log telegram errors
    Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    // Silence is golden!
    // Uncomment this to catch log initialisation errors
    echo $e;
}
