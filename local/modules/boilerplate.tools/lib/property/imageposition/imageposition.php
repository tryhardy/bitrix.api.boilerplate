<?php

namespace Boilerplate\Tools\Property\ImagePosition;

use Bitrix\Main\Loader;
use CIBlockElement;
use CJSCore;
use CIBlockSection;
use CFile;

/**
 * Отображает в админке картинку, на которой расположена точка.
 * Точку можно двигать. В поле записываются координаты точки в %
 */
class ImagePosition
{
    const PROPERTY_USER_TYPE = "UplabImagePosition";
    const PROPERTY_ID = "image.position";

    function __construct()
    {
        if (!Loader::includeModule("iblock")) {
            return;
        }
    }

    public static function getUserTypeDescription()
    {
        return [
            "PROPERTY_TYPE"             => "S",
            "USER_TYPE"                 => self::PROPERTY_USER_TYPE,
            "DESCRIPTION"               => "Свойство «Координаты точки на картинке»",
            "GetPropertyFieldHtml"      => [self::class, "GetPropertyFieldHtml"],
            "GetPropertyFieldHtmlMulty" => [self::class, "GetPropertyFieldHtmlMulty"],
            "GetPublicEditHTML"         => [self::class, "GetPropertyFieldHtml"],
            "GetPublicEditHTMLMulty"    => [self::class, "GetPropertyFieldHtmlMulty"],
            "GetAdminFilterHTML"        => [self::class, "GetAdminFilterHTML"],
            "PrepareSettings"           => [self::class, "PrepareSettings"],
            "GetSettingsHTML"           => [self::class, "GetSettingsHTML"],
            "ConvertToDB"               => [self::class, "ConvertToDB"],
            "ConvertFromDB"             => [self::class, "ConvertFromDB"],
        ];
    }

    public static function ConvertToDB(
        /** @noinspection PhpUnusedParameterInspection */
        $arProperty,
        $value
    ) {
        return $value;
    }

    public static function ConvertFromDB(
        /** @noinspection PhpUnusedParameterInspection */
        $arProperty,
        $value
    ) {
        return $value;
    }

    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
    {
        $settings = self::PrepareSettings($arProperty);

        $arPropertyFields = [
            "HIDE" => ["ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT"],
        ];

        return '
			<tr valign="top">
				<td>URL картинки:</td>
				<td>
					<input type="text"
					       size="50"
					       name="' . $strHTMLControlName["NAME"] . '[imgUrl]" value="' . $settings["imgUrl"] . '">
				</td>
			</tr>
			<tr valign="top">
				<td>Код свойства раздела,<br>в котором хранится изображение</td>
				<td>
					<input type="text"
					       size="50"
					       name="' . $strHTMLControlName["NAME"] . '[sectionImgCode]" value="' . $settings["sectionImgCode"] . '">
				</td>
			</tr>
			<tr valign="top">
				<td>
					Дополнительный CSS для метки выбора<br>
					<em style="white-space: nowrap">
						(По умолч.: <strong>transform:&nbsp;translate(-50%,-50%);</strong>)
					</em>
				</td>
				<td><input type="text" size="50" name="' . $strHTMLControlName["NAME"] . '[pinCss]" value="' . $settings["pinCss"] . '"></td>
			</tr>
			';
    }

    public static function PrepareSettings($arProperty)
    {
        $imgUrl = "";

        if (empty($imgUrl) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
            $imgUrl = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["imgUrl"]));
        }
        $imgUrl = $imgUrl ?: "";

        if (empty($sectionImgCode) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
            $sectionImgCode = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["sectionImgCode"]));
        }

        if (empty($pinCss) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
            $pinCss = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["pinCss"]));
        }

        if (is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["multiple"] === "Y") {
            $multiple = "Y";
        } else {
            $multiple = "N";
        }

        return compact("multiple", "imgUrl", "sectionImgCode", "pinCss");
    }

    public static function PrepareImageUrl($arProperty)
    {
        $settings = self::PrepareSettings($arProperty);
        $elementID = intval($_REQUEST["ID"]);

        /**
         * Получаем из родительского раздела изображение,
         * на которое будут ставиться точки.
         * По умолчанию используется стандартное изображение раздела.
         */
        if (empty($imgUrl) && !empty($settings["sectionImgCode"]) && !empty($elementID)) {
            Loader::includeModule("iblock");
            $sectionID = "";

            $res = CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $arProperty["IBLOCK_ID"],
                    'ID'        => $elementID,
                ],
                false,
                ["nTopCount" => 1],
                ["ID", "NAME", "IBLOCK_SECTION_ID"]
            );
            if ($element = $res->Fetch()) {
                $sectionID = $element["IBLOCK_SECTION_ID"];
            }

            if (!empty($sectionID)) {
                $res = CIBlockSection::GetList(
                    false,
                    [
                        'IBLOCK_ID' => $arProperty["IBLOCK_ID"],
                        'ID'        => $sectionID,
                    ],
                    [],
                    [$settings["sectionImgCode"]]
                );
                if ($section = $res->Fetch()) {
                    if (!empty($section[$settings["sectionImgCode"]])) {
                        $arImg = CFile::GetFileArray($section[$settings["sectionImgCode"]]);
                        if (!empty($arImg["SRC"])) {
                            $settings["imgUrl"] = $arImg["SRC"];
                        }
                    }
                }
            }
        }

        return $settings;
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $settings = self::PrepareImageUrl($arProperty);

        $wrapperClass = "uplab-image-position-" . randString(6, "abcdefghijklnmopqrstuvwxyz");

        $html = "";
        $html .= "<div class=\"{$wrapperClass}\">";

        if ($v = $settings["pinCss"]) {
            $html .= "
			<style>.{$wrapperClass} .uplab-image-position .dot { {$v}; }</style>
			";
        }

        if (empty($settings["imgUrl"])) {
            $html .= "<div style='color: #aaa;'>";
            $html .= "Необходимо указать изображение!<br>";
            $html .= "Изображение из раздела будет загружено только после сохранения элемента.";
            $html .= "</div>";
        } else {
            $html .= "<div class=\"uplab-image-position\">";
            $html .= "  <img style=\"max-width: unset; max-height: 90vh;\" src=\"" . $settings["imgUrl"] . "\" draggable=\"false\">";
            $html .= "</div><br>";
            $html .= "<input type=\"text\" size=\"35\" name=\"" . $strHTMLControlName["VALUE"] . "\" ";
            $html .= "       style=\"text-align: center;\" ";
            $html .= "       value=\"" . $value["VALUE"] . "\">";
        }

        self::includeAssets();

        $html .= "</div>";

        return $html;
    }

    public static function includeAssets()
    {
        global $APPLICATION;

        # ====== предотвращение повторного вызова функции ====== >>>
        static $flag = false;
        if ($flag == true) {
            return;
        }
        $flag = true;
        # <<< ======================================================

        CJSCore::Init('jquery');

        $APPLICATION->SetAdditionalCSS(
            'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css'
        );
        /** @noinspection PhpDeprecationInspection */
        $APPLICATION->AddHeadScript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js');

        $APPLICATION->SetAdditionalCSS('/local/modules/boilerplate.tools/lib/property/imageposition/style.css');
        /** @noinspection PhpDeprecationInspection */
        $APPLICATION->AddHeadScript('/local/modules/boilerplate.tools/lib/property/imageposition/script.js');
    }

    function GetPropertyFieldHtmlMulty(
        /** @noinspection PhpUnusedParameterInspection */
        $arProperty,
        $value,
        $strHTMLControlName
    ) {
        $html = '';

        return $html;
    }

    function GetAdminFilterHTML(
        /** @noinspection PhpUnusedParameterInspection */
        $arProperty,
        $strHTMLControlName
    ) {
        $html = '';

        return $html;
    }

    function GetOptionsHtml(
        /** @noinspection PhpUnusedParameterInspection */
        $arProperty,
        $values,
        &$bWasSelect
    ) {
        $options = '';

        return $options;
    }
}
