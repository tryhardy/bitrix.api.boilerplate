<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Engine\Response\Json;
use Boilerplate\Tools\Page as PageData;

class NewsDetail extends Controller
{
    public function configureActions(): array
    {
        return [
            'get' => [
                'prefilters'  => [],
                'postfilters' => [],
            ],
        ];
    }

    /**
     * @return array|array[]|Json
     */
    public function getAction(string $news, string $lang = '')
    {
        $lang = $this->checkLang($lang);

        $pageData = (new PageData\NewsDetail($news, $lang))->getData();

        if (!empty($pageData)) {
            return $pageData;
        }

        return $this->get404Response($lang);
    }
}
