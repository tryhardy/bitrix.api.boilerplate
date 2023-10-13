<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Engine\Response\Json;
use Boilerplate\Tools\Form\Writetous;
use Boilerplate\Tools\Helper;

class Form extends Controller
{
    public function configureActions(): array
    {
        return [
            'write' => [
                'prefilters'  => [
	                //checking sessid param in request
	                new \Bitrix\Main\Engine\ActionFilter\Csrf(),
                ],
                'postfilters' => [],
            ],
        ];
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function writeAction(string $form, string $lang = ''): Json
    {
        $lang = $this->checkLang($lang);

        return match ($form) {
            'writetous' => (new Writetous($lang))->addFormResult($_REQUEST ?: []),
            default => $this->get404Response($lang),
        };
    }
}
