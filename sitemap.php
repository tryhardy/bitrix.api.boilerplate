<?php

use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule('iblock');

$domain = 'https://boilerplate';// TODO: Заменить на реальные данные


/*
 * Получение данных
 */
$cacheId = "sitemap";
$cacheDir = $cacheId;
$cacheTime = 3600 * 24;

$cache = Bitrix\Main\Data\Cache::createInstance();
if ($cache->initCache($cacheTime, $cacheId, $cacheDir)) {
    $pages = $cache->getVars();
} elseif ($cache->startDataCache()) {
    //***REQUEST***

    $pages = [];

    // Страницы
    $arOrder = [
        'ID' => 'DESC',
    ];
    $arFilter = [
        'IBLOCK_CODE' => 'pages',// TODO: Заменить на реальные данные
        'ACTIVE'      => 'Y',
    ];
    $arGroupBy = false;
    $arNavStartParams = false;
    $arSelectFields = [
        'ID',
        'NAME',
        'CODE',
        'LANG_DIR',
        'TIMESTAMP_X_UNIX',
        'PROPERTY_LINK',
    ];

    $res = CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);

    while ($el = $res->Fetch()) {
        if (empty($el['LANG_DIR']) ||
            empty($el['PROPERTY_LINK_VALUE']) ||
            empty($el['TIMESTAMP_X_UNIX'])) {
            continue;
        }

        $pages[] = [
            'loc'     => $domain . str_replace('//', '/', ($el['LANG_DIR'] . $el['PROPERTY_LINK_VALUE'])),
            'lastmod' => date('c', $el['TIMESTAMP_X_UNIX']),
        ];
    }

    unset($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $res, $el);

    // Элементы ИБ
    $arOrder = [
        'ID' => 'DESC',
    ];
    $arFilter = [
        'IBLOCK_CODE' => [
            'news',// TODO: Заменить на реальные данные
        ],
        'ACTIVE'      => 'Y',
    ];
    $arGroupBy = false;
    $arNavStartParams = false;
    $arSelectFields = [];

    $res = CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);

    while ($ob = $res->GetNextElement()) {
        $el = $ob->GetFields();

        if (empty($el['DETAIL_PAGE_URL']) ||
            empty($el['TIMESTAMP_X_UNIX'])) {
            continue;
        }

        $pages[] = [
            'loc'     => $domain . $el['DETAIL_PAGE_URL'],
            'lastmod' => date('c', $el['TIMESTAMP_X_UNIX']),
        ];
    }

    unset($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $res, $ob, $el);

    // Разделы ИБ
    $arOrder = [
        "ID" => "DESC",
    ];
    $arFilter = [
        'IBLOCK_CODE' => [
            'mediagallery',// TODO: Заменить на реальные данные
        ],
        "ACTIVE"      => "Y"
    ];
    $bIncCnt = true;
    $arSelect = [];
    $arNavStartParams = false;

    $res = CIBlockSection::GetList($arOrder, $arFilter, $bIncCnt, $arSelect, $arNavStartParams);

    while ($ob = $res->GetNextElement()) {
        $sect = $ob->GetFields();

        if (empty($sect['SECTION_PAGE_URL']) ||
            empty($sect['TIMESTAMP_X'])) {
            continue;
        }

        $pages[] = [
            'loc'     => $domain . $sect['SECTION_PAGE_URL'],
            'lastmod' => date('c', strtotime($sect['TIMESTAMP_X'])),
        ];
    }

    unset($arOrder, $arFilter, $bIncCnt, $arSelect, $arNavStartParams, $res, $ob, $sect);

    //***REQUEST***

    if (!$pages) {
        $cache->abortDataCache();
    }

    $cache->endDataCache($pages);
}


/*
 * Вывод данных
 */
$output = '<?xml version="1.0" encoding="UTF-8"?>';
$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

foreach ($pages as $page) {
    $output .= '<url>';
    $output .= '<loc>' . $page['loc'] . '</loc>';
    $output .= '<lastmod>' . $page['lastmod'] . '</lastmod>';
    $output .= '</url>';
}

$output .= '</urlset>';

ob_clean();
header("Content-type: text/xml");
echo $output;
