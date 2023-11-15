<?php

namespace Boilerplate\Tools\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

class ErrorTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'error';
    }

    /**
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', [
                'primary'      => true,
                'autocomplite' => true,
            ]),
            new Entity\DatetimeField('UF_DATE', [
                'required' => true,
            ]),
            new Entity\StringField('UF_TYPE', []),
            new Entity\StringField('UF_TEXT', [
                'required' => true,
            ]),
        ];
    }

    /**
     * @throws \Exception
     */
    public static function log(string $text, string $type = '')
    {
        self::add([
            'UF_DATE' => new DateTime(),
            'UF_TYPE' => $type,
            'UF_TEXT' => $text,
        ]);
    }
}
