<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Engine\Response\Json;
use Boilerplate\Tools\TextPage as TextPageData;

class TextPage extends Controller
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
            'personal-data' => (new TextPageData\PersonalData($lang))->getData(),
            'policy' => (new TextPageData\Policy($lang))->getData(),
            default => $this->get404Response($lang),
        };
    }
}
