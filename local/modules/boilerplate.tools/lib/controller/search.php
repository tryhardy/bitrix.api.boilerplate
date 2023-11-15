<?php

namespace Boilerplate\Tools\Controller;

class Search extends Controller
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
     * @throws \Bitrix\Main\LoaderException
     */
    public function getAction(
        string $search = '',
        string $lang = '',
        string $sort = 'rank',
        $offset = 0,
        $limit = 10,
    ): array {
        if (empty($search)) {
            return [];
        }

        return (new \Boilerplate\Tools\Model\Search($this->checkLang($lang)))->get($search, $sort, $offset, $limit);
    }
}
