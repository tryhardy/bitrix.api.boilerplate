<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Engine\Response\Json;
use Boilerplate\Tools\Page as PageData;

class Page extends Controller
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
     * @return array|Json
     */
    public function getAction(string $page, string $lang = '')
    {
        $lang = $this->checkLang($lang);

        return match ($page) {
	        'layout' => (new PageData\Layout($lang))->getData(),
            'main' => (new PageData\Main($lang))->getData(),
            '404' => (new PageData\NotFound404($lang))->getData(),
            'news' => (new PageData\News($lang))->getData(),
            'search' => (new PageData\Search($lang))->getData(),
            default => $this->get404Response($lang),
        };
    }
}
