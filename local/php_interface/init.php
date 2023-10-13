<?php

/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;

/*
 * Автозагрузчик
 */
include_once $_SERVER["DOCUMENT_ROOT"] . "/local/vendor/autoload.php";

/*
 * Вывод логов
 */
if ($dir = __DIR__ . "/log/") {
    define("LOG_FILENAME", $dir . date("Ymd") . ".log");
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }
}

/*
 * Модуль
 */
Loader::includeModule('boilerplate.tools');

/*
 * События
 */
$eventManager = EventManager::getInstance();

// Свойство Координаты точки на картинке
$eventManager->addEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    [
        'Boilerplate\Tools\Property\ImagePosition\ImagePosition',
        'getUserTypeDescription',
    ]
);
