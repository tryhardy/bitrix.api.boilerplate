<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Engine\Response\Json;
use Boilerplate\Tools\Common as CommonData;

class News extends Controller
{
    public function configureActions(): array
    {
        return [
            'get' => [
                'prefilters'  => [],
                'postfilters' => [],
            ]
        ];
    }

    /**
     * @return array|array[]|Json
     */
    public function getAction(
        string $limit = '',
        string $offset = '',
        string $lang = ''
    ) {
        $lang = $this->checkLang($lang);

        $pageData = (new CommonData\News((int)$limit, (int)$offset, $lang))->getData();

        if (!empty($pageData)) {
            return $pageData;
        }

        return $this->get404Response($lang);
    }
}
