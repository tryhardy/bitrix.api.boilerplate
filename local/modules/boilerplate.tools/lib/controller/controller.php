<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Engine\Controller as BitrixController;
use Bitrix\Main\Engine\Response\Json;
use Boilerplate\Tools\Content\Content;

class Controller extends BitrixController
{
    protected function checkLang(string $lang): string
    {
        if (!in_array($lang, ['ru', 'en', 'cn'])) {
            return 'ru';
        }

        return $lang;
    }

    protected function get404Response(string $lang): Json
    {
        $common = (Content::getInstance())->getContent('common', $lang)['common'];

        $res = new Json();
        $res->setStatus(404);
        $res->setData([
            'data'   => [
                'title' => $common['page_not_found'],
                'text'  => $common['page_not_found_message'],
            ],
            'status' => 'success',
            'errors' => [],
        ]);

        return $res;
    }
}
