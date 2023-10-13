<?php

namespace Boilerplate\Tools;

use Boilerplate\Tools\Content\Content;

class Helper
{
	const SESSID_CODE = 'sessid';
    final public const MODULE_ID = "boilerplate.tools";

	public static function getSessid() : string
	{
		return bitrix_sessid();
	}

    public static function getAbsoluteUrl(string $url): string
    {
        if (!$url) {
            return '';
        }

        $domain = 'https://' . $_SERVER['SERVER_NAME'];

        return $domain . $url;
    }

    /**
     * Возвращает мультибайтовую строку с заглавным первым символом
     */
    public static function getMbUcfirstString(string $string): string
    {
        $string = mb_strtolower($string);

        $first = mb_substr($string, 0, 1);
        $rest = mb_substr($string, 1);

        return mb_strtoupper($first) . $rest;
    }

    public static function clearPhone(string $phone): string
    {
        return preg_replace('![^0-9+]+!', '', $phone);
    }

    public static function clearLink(string $link): string
    {
        return str_replace(['http://', 'https://'], '', $link);
    }

    /**
     * Возвращает выражение, в зависимости от колличества
     *
     * @param int $count Колличество
     * @param array $titles Выражения. Пример: ['Сидит %d котик', 'Сидят %d котика', 'Сидит %d котиков']
     */
    public static function getNumberWord(int $count, array $titles = []):string
    {
        $cases = [2, 0, 1, 1, 1, 2];

        $format = $titles[($count % 100 > 4 && $count % 100 < 20) ? 2 : $cases[min($count % 10, 5)]];

        return sprintf($format, $count);
    }

    public static function getAccordionList(string $lang, string $pageCode = '', int $sectionId = 0): array
    {
        // возможны несколько групп аккордеонов, ищем подразделы
        $sections = \Bitrix\Iblock\SectionTable::getList([
            'filter' => [
                'ACTIVE'         => true,
                'IBLOCK.CODE'    => 'accordions',
                'IBLOCK.TYPE.ID' => 'content_' . $lang,
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'DESCRIPTION',
                'IBLOCK_SECTION_ID',
                'DEPTH_LEVEL',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        $parentSectionId = 0;
        $sectionIdList = [];
        foreach ($sections as $section) {
            $sectionIdList[$section['IBLOCK_SECTION_ID']][] = $section['ID'];

            if (($pageCode && $section['CODE'] == $pageCode)
                || (($sectionId != 0) && ($section['ID'] == $sectionId))
            ) {
                $parentSectionId = $section['ID'];
            }
        }

        $className = "\Bitrix\Iblock\Elements\ElementAccordions{$lang}Table";

        $cardList = [];
        if (!class_exists($className)) {
            return $cardList;
        }

        $filter = [
            'ACTIVE'                => true,
            'IBLOCK_SECTION.ACTIVE' => true,
        ];
        if ($sectionId) {
            $filter['IBLOCK_SECTION.ID'] = $sectionId;
        } elseif ($parentSectionId && $sectionIdList[$parentSectionId]) {
            $filter['IBLOCK_SECTION.ID'] = $sectionIdList[$parentSectionId];
        } else {
            $filter['IBLOCK_SECTION.CODE'] = $pageCode;
        }

        $elements = $className::getList([
            'filter' => $filter,
            'order'  => [
                'IBLOCK_SECTION.SORT' => 'ASC',
                'SORT'                => 'ASC',
                'ID'                  => 'DESC',
            ],
            'select' => [
                'ID',
                'NAME',
                'IBLOCK_SECTION',
                'PREVIEW_TEXT',
                'SUP_INFO',
                'LINK_LIST',
                'BAGGAGE_TYPE',
                'FACTOID_LIST',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        $sectionList = [];
        foreach ($elements as $element) {
            $sectionList[$element->getIblockSection()->getCode()]['ELEMENTS'][] = $element;
        }

        $accordionList = [];
        foreach ($sectionList as $section) {
            $itemList = [];
            foreach ($section['ELEMENTS'] as $element) {
                $item = [
                    'id'     => $element->getId(),
                    'toggle' => $element->getName(),
                ];

                if ($element->getSupInfo() && $element->getSupInfo()->getValue()) {
                    $item['toggleInfo'] = $element->getSupInfo()->getValue();
                }

                if ($element->getPreviewText()) {
                    $item['text'] = $element->getPreviewText();
                }

                if ($element->getLinkList() && $linkListInfo = $element->getLinkList()->getAll()) {
                    $linkIdList = [];
                    foreach ($linkListInfo as $link) {
                        $linkIdList[] = $link->getValue();
                    }

                    $item['links'] = self::getAccordionLinkList($linkIdList, $lang);
                }

                if ($element->getBaggageType() && $baggageTypeId = $element->getBaggageType()->getValue()) {
                    $item['cards'] = self::getBaggageType($baggageTypeId, $lang);
                }

                if ($element->getFactoidList() && $factoidIdList = $element->getFactoidList()->getAll()) {
                    foreach ($factoidIdList as $factoid) {
                        $item['factoids'][] = [
                            'title'  => $factoid->getValue(),
                            'number' => $factoid->getDescription(),
                        ];
                    }
                }

                if ($item) {
                    $itemList[] = $item;
                }
            }

            if (!$sectionIdList[$parentSectionId] && !$sectionId) {
                return $itemList;
            }

            $accordionList[] = [
                'accordion' => [
                    'items' => $itemList,
                ],
                'mainText'  => [
                    'id'    => $element->getIblockSection()->getCode(),
                    'theme' => 'baggage',
                    'title' => $element->getIblockSection()->getName(),
                    'text'  => $element->getIblockSection()->getDescription(),
                ],
            ];
        }

        return $accordionList;
    }

    public static function getAccordionLinkList(array $linkId, string $lang): array
    {
        $a = [];

        $className = "\Bitrix\Iblock\Elements\ElementAccordionsLink{$lang}Table";

        if (!class_exists($className)) {
            return $a;
        }

        $elements = $className::getList([
            'filter' => [
                'ACTIVE' => true,
                'ID'     => $linkId,
            ],
            'select' => [
                'ID',
                'NAME',
                'PREVIEW_TEXT',
                'ORANGE',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        $linkList = [];
        foreach ($elements as $element) {
            $item = [];
            if ($element->getPreviewText()) {
                $item = [
                    'href' => $element->getPreviewText(),
                    'text' => $element->getName(),
                ];

                if ($element->getOrange() && $element->getOrange()->getValue()) {
                    $item['theme'] = 'orange';
                }
            }

            if ($item) {
                $linkList[] = $item;
            }
        }

        return $linkList;
    }

    public static function getBaggageType(int $elementIdList, string $lang): array
    {
        $cardList = [];

        $className = "\Bitrix\Iblock\Elements\ElementBaggageType{$lang}Table";

        if (!class_exists($className)) {
            return $cardList;
        }

        $elements = $className::getList([
            'filter' => [
                'ACTIVE'            => true,
                'IBLOCK_SECTION.ID' => $elementIdList,
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'IMAGE',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        foreach ($elements as $element) {
            $cardList[] = [
                'text' => $element->getName(),
                'icon' => self::getAbsoluteUrl(\CFile::GetPath($element->getImage()->getValue())),
            ];
        }

        return $cardList;
    }

    /**
     * Список документов
     */
    public static function getDocuments(string $lang, int $sectionId = 0, string $pageCode = ''): array
    {
        $common = (Content::getInstance())->getContent('common', $lang)['common'];

        $a = [];

        $className = "\Bitrix\Iblock\Elements\ElementDocuments{$lang}Table";

        if (!class_exists($className)) {
            return $a;
        }

        $filter = [
            'ACTIVE'                => true,
            'IBLOCK_SECTION.ACTIVE' => true,
        ];

        if ($sectionId) {
            $filter['IBLOCK_SECTION.ID'] = $sectionId;
        } else {
            $filter['IBLOCK_SECTION.CODE'] = $pageCode;
        }

        $elements = $className::getList([
            'filter' => $filter,
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'IBLOCK_SECTION',
                'DOCUMENT',
                'ACTIVE_FROM',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        $title = '';
        $documentList = [];
        foreach ($elements as $element) {
            if (!$file = $element->getDocument()?->getValue()) {
                continue;
            }

            $arDocument = \CFile::MakeFileArray($element->getDocument()->getValue());

            $i = [
                'name' => $element->getName(),
                'size' => \CFile::FormatSize($arDocument['size']),
                'link' => self::getAbsoluteUrl(\CFile::getPath($file)),
            ];

            if (!empty($element->getActiveFrom()) &&
                !empty($common['month_genitive_' . $element->getActiveFrom()?->format('n')])) {
                $i['date'] = $element->getActiveFrom()?->format('d ' . $common['month_genitive_' . $element->getActiveFrom()?->format('n')] . ' Y');
            }

            $documentList[] = $i;

            $title = $element->getIblockSection()->getName();
        }

        if ($documentList) {
            $a = [
                'title' => $title,
                'files' => $documentList,
            ];
        }

        return $a;
    }

    /**
     * Услуги
     */
    public static function getServices(string $pageCode, string $lang): array
    {
        $a = [];

        $className = "\Bitrix\Iblock\Elements\ElementServices{$lang}Table";

        if (!class_exists($className)) {
            return [];
        }

        $elements = $className::getList([
            'filter' => [
                'ACTIVE'              => true,
                'IBLOCK_SECTION.CODE' => $pageCode,
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'IBLOCK_SECTION',
                'PREVIEW_PICTURE',
                'PREVIEW_TEXT',
                'PRICE',
                'ICON',
                'LINK',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        foreach ($elements as $element) {
            $i = [
                'name' => $element->getName(),
            ];

            if ($price = $element->getPrice()->getValue()) {
                $i['price'] = $price;
            }

            if ($icon = $element->getIcon()->getValue()) {
                $i['icon'] = Helper::getAbsoluteUrl(\CFile::GetPath($icon));
            }

            if ($image = $element->getPreviewPicture()) {
                $i['image'] = [
                    'src' => Helper::getAbsoluteUrl(\CFile::GetPath($image)),
                ];
            }

            $a[] = $i;
        }

        return $a;
    }

    public static function getContacts(
        string $pageCode,
        string $lang,
        string $phoneLabel = '',
        string $workHoursLabel = ''
    ): array {
        $a = [];

        $className = "\Bitrix\Iblock\Elements\ElementContacts{$lang}Table";

        if (!class_exists($className)) {
            return [];
        }

        $elements = $className::getList([
            'filter' => [
                'ACTIVE'              => true,
                'IBLOCK_SECTION.CODE' => $pageCode,
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'PREVIEW_TEXT',
                'LINK',
                'EMAIL',
                'PHONE',
                'WORK_TIME',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        foreach ($elements as $element) {
            $i = [
                'title' => $element->getName(),
            ];

            // Email
            if ($email = $element->getEmail()->getValue()) {
                $i['link'] = [
                    'text' => $email,
                    'href' => 'mailto:' . $email,
                ];
            }

            // Ссылка
            $link = $element->getLink()->getValue();
            $linkText = $element->getLink()->getDescription();

            if ($link && $linkText) {
                $i['link'] = [
                    'text' => $linkText,
                    'href' => $link,
                    'icon' => '24/ar_big_up',
                ];
            }

            // Телефон
            $phone = $element->getPhone()->getValue();
            $phonePostfix = $element->getPhone()->getDescription();

            if ($phone) {
                $p = [
                    'label' => $phoneLabel,
                    'text'  => $phone,
                    'href'  => 'tel:' . Helper::clearPhone($phone),
                ];

                if ($phonePostfix) {
                    $p['postfix'] = $phonePostfix;
                }

                $i['desc'][] = $p;
            }

            // Режим работы
            if ($work = $element->getWorkTime()->getValue()) {
                $i['desc'][] = [
                    'label' => $workHoursLabel,
                    'text'  => $work,
                ];
            }

            // Описание
            if ($text = $element->getPreviewText()) {
                $i['text'] = $text;
            }

            $a['contacts'][] = $i;
        }

        return $a;
    }

    public static function getInstruction(string $lang, string $pageCode): array
    {
        $a = [];

        $className = "\Bitrix\Iblock\Elements\ElementInstruction{$lang}Table";

        if (!class_exists($className)) {
            return [];
        }

        $elements = $className::getList([
            'filter' => [
                'ACTIVE'              => true,
                'IBLOCK_SECTION.CODE' => $pageCode,
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'IBLOCK_SECTION',
                'PREVIEW_TEXT',
                'DETAIL_TEXT',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        foreach ($elements as $element) {
            $item = [];
            if ($element->getDetailText()) {
                $item['text'] = $element->getDetailText();
            }

            if ($element->getPreviewText()) {
                $item['toggleInfo'] = $element->getPreviewText();
            }

            if ($item) {
                $item['id'] = $element->getId();
                $item['toggle'] = $element->getName();
            }

            $a['items'][] = $item;
        }

        return $a;
    }
}
