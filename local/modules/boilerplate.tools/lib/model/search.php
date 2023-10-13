<?php

namespace Boilerplate\Tools\Common;

use Bitrix\Main\Loader;
use Boilerplate\Tools\Content\Content;

class Search
{
    private int $pagesIblockId;

    public function __construct(private readonly string $lang)
    {
        Loader::includeModule('iblock');

        $this->content = (Content::getInstance())->getContent('search', $this->lang)['search'];
        $this->common = (Content::getInstance())->getContent('common', $this->lang)['common'];
    }

    /**
     * Возвращает форматированные результаты поиска
     * @throws \Bitrix\Main\LoaderException
     */
    public function get(string $search, string $type = 'full', string $sort = 'rank', $offset = null, $limit = 10, string $filter = ''): array
    {
        // Результаты поиска
        $searchResults = $this->getSearchResults($search, $sort);

        // Статические страницы из соответствующего ИБ
        $staticPages = $this->getStaticPages();

        // Данные инфоблоков
        $iblocks = $this->getIblocks();

        // Категории результатов поиска
        $categories = [];
        foreach ($searchResults as $result) {
            $category = $iblocks[$result['PARAM2']];

            $categories[$category['CODE']]['NAME'] = $category['NAME'];
            $categories[$category['CODE']]['COUNT'] += 1;
        }

        // Фильтр по категории
        $searchResultsFilter = [];
        if ($filter &&
            $filter !== 'all') {
            foreach ($searchResults as $item) {
                if ($iblocks[$result['PARAM2']] &&
                    $filter === $iblocks[$item['PARAM2']]['CODE']
                ) {
                    $searchResultsFilter[] = $item;
                }
            }
        } else {
            $searchResultsFilter = $searchResults;
        }

        // Пагинация
        $searchResultsFilterOffsetLimit = array_slice($searchResultsFilter, (int)$offset, ((int)$limit) ?: null);

        // Данные результатов поиска
        $results = [];
        foreach ($searchResultsFilterOffsetLimit as $result) {
            // Заголовок
            $r = [
                'title' => $result['TITLE'],
            ];

            if ($result['MODULE_ID'] === 'iblock'
                && $result['PARAM2'] == $this->pagesIblockId
                && $staticPages[$result['ITEM_ID']]) {
                // Статическая страница

                if ($type !== 'short') {
                    // Ссылка
                    if ($staticPages[$result['ITEM_ID']]['LINK']) {
                        $r['href'] = $staticPages[$result['ITEM_ID']]['LINK'];
                    }

                    $r['category'] = $this->content['pages'];
                } else {
                    // Ссылка
                    if ($staticPages[$result['ITEM_ID']]['LINK']) {
                        $r['link'] = $staticPages[$result['ITEM_ID']]['LINK'];
                    }
                }
            } else {
                // Элемент ИБ

                if ($type !== 'short') {
                    // Ссылка
                    if ($result['URL_WO_PARAMS']) {
                        $r['href'] = $result['URL_WO_PARAMS'];
                    }

                    // Категория
                    if ($iblocks[$result['PARAM2']]['NAME']
                        && $type !== 'short') {
                        $category = $iblocks[$result['PARAM2']];

                        $r['category'] = $category['NAME'];
                    }

                    // Дата
                    if (!empty($result['DATE_CHANGE'])) {
                        $time = strtotime((string)$result['DATE_CHANGE']);

                        if (!empty($this->common['month_genitive_' . date('n', $time)])) {
                            $month = $this->common['month_genitive_' . date('n', $time)];

                            $r['desc'] = date('d', $time) . ' ' . $month . ' \'' . date('y', $time);
                        }
                    }
                } else {
                    // Ссылка
                    if ($result['URL_WO_PARAMS']) {
                        $r['link'] = $result['URL_WO_PARAMS'];
                    }
                }
            }

            $results[] = $r;
        }

        if ($results) {
            // Результаты поиска есть

            $a = [
                'items' => $results,
            ];

            if ($type !== 'short') {
                $a['count'] = count($searchResultsFilter);

                // Фильтры
                $a['filters'][] = [
                    'id'   => 'all',
                    'text' => $this->content['results_found'] . count($searchResults),
                ];

                if ($categories) {
                    foreach ($categories as $categoryCode => $categoryArr) {
                        $a['filters'][] = [
                            'id'   => $categoryCode,
                            'text' => $categoryArr['NAME'] . ' ' . $categoryArr['COUNT'],
                        ];
                    }
                }
            }
        } else {
            // Результатов поиска нет

            if ($type !== 'short') {
                $a = [
                    'message' => [
                        'title' => $this->content['no_data_title'],
                        'text'  => $this->content['no_data_text'],
                    ]
                ];
            } else {
                $a = [
                    'message' => [
                        'text' => $this->content['no_data_text_short'],
                    ]
                ];
            }
        }

        return $a;
    }

    /**
     * Возвращает результаты поиска
     * @throws \Bitrix\Main\LoaderException
     */
    private function getSearchResults(string $search, string $sort = 'rank'): array
    {
        $a = [];

        Loader::includeModule('search');

        if ($sort === 'rank') {
            $sorting = [
                'CUSTOM_RANK' => 'DESC',
                'RANK'        => 'DESC',
                'DATE_CHANGE' => 'DESC',
            ];
        } else {
            $sorting = [
                'DATE_CHANGE' => 'DESC',
                'CUSTOM_RANK' => 'DESC',
                'RANK'        => 'DESC',
            ];
        }

        $cSearch = new \CSearch;
        $cSearch->Search(
            [
                'QUERY'   => $search,
                'SITE_ID' => $this->lang,
            ],
            $sorting
        );

        $cSearch->NavStart(200);

        while ($result = $cSearch->Fetch()) {
            $a[] = $result;
        }

        return $a;
    }

    /**
     * Возвращает список статических страниц
     */
    private function getStaticPages(): array
    {
        $page = new Pages($this->lang);

        $pages = $page->getStaticPages();

        $this->pagesIblockId = $page->pagesIblockId;

        return $pages;
    }

    /**
     * Возвращет инфоблоки
     */
    private function getIblocks(): array
    {
        $a = [];

        $cIBlock = \CIBlock::GetList(
            [],
            [
                'SITE_ID' => $this->lang,
                'ACTIVE'  => 'Y',
            ],
            true
        );

        while ($iblock = $cIBlock->Fetch()) {
            $a[$iblock['ID']] = $iblock;
        }

        return $a;
    }
}
