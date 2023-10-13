<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Engine\Response\Json;

class Preflight extends Controller
{
    public function configureActions(): array
    {
        return [
            'preflight' => [
                'prefilters'  => [],
                'postfilters' => [],
            ]
        ];
    }

    /**
     * Обработка preflight запроса для сложных запросах при CORS
     */
    public function preflightAction(): Json
    {
        $res = new Json();
        $res->setStatus(200);
        return $res;
    }
}
