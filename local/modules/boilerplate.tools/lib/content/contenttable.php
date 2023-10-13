<?php

namespace Boilerplate\Tools\Content;

use Bitrix\Main\Entity;

class ContentTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'content';
    }

    public static function getMap(): array
    {
        return [
            (new Entity\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new Entity\StringField('UF_CODE'))->configureRequired(),
            (new Entity\StringField('UF_PAGE'))->configureRequired(),
            new Entity\StringField('UF_BLOCK'),
            new Entity\StringField('UF_VALUE_RU'),
            new Entity\StringField('UF_VALUE_EN'),
            new Entity\StringField('UF_VALUE_CN'),
        ];
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPageContent(string $pageCode, string $languageId): array
    {
        $content = self::getPageContentFromBase($pageCode, $languageId);

        return self::groupPageContent($content);
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function getPageContentFromBase(string $pageCode, string $languageId): array
    {
        $contents = self::getList([
            'select' => [
                'UF_CODE',
                'UF_BLOCK',
                'VALUE' => 'UF_VALUE_' . strtoupper($languageId),
            ],
            'filter' => [
                '=UF_PAGE' => $pageCode,
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchAll();

        return $contents;
    }

    protected static function groupPageContent(array $input): array
    {
        $output = [];

        if (empty($input)) {
            return $output;
        }

        foreach ($input as $item) {
            if (!$item['UF_CODE'] || !$item['UF_BLOCK']) {
                continue;
            }

            $output[$item['UF_BLOCK']][$item['UF_CODE']] = $item['VALUE'];
        }

        return $output;
    }
}
