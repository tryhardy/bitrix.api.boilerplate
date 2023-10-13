<?php

namespace Boilerplate\Tools\Page;

use Boilerplate\Tools\Helper;

class Main extends Page
{
    protected function getCode(): string
    {
        return 'main';
    }

    protected function setData(): array
    {
        return [
            'meta'   => $this->getMeta(),
            'hero'   => $this->getHero('hero'),
            'banner' => $this->getBanner('banner'),
            'news'   => $this->getNews('news'),
        ];
    }

    /**
     * Мета-теги
     */
    private function getMeta(): array
    {
        return $this->page->getPageMeta();
    }

    /**
     * Первый экран
     */
    private function getHero(string $blockCode): array
    {
        $a = [];

        // Заголовок
        if ($pageH1 = $this->page->getPageMeta()['pageH1']) {
            $a['title'] = $pageH1;
        } elseif ($this->content[$blockCode]['title']) {
            $a['title'] = $this->content[$blockCode]['title'];
        }

        // Описание
        if ($describe = $this->page->getDescribe()) {
            $a['text'] = $describe;
        } elseif ($this->content[$blockCode]['text']) {
            $a['text'] = $this->content[$blockCode]['text'];
        }

        // Хлебные крошки
        $breadcrumbs = $this->page->getPageBreadcrumbs();
        if ($breadcrumbs) {
            $a['breadcrumbs'] = $breadcrumbs;
        }

        // Фоновые изображения
        if ($desktop = $this->page->getPageImages()['src']) {
            $a['bg']['src'] = $desktop;
        } elseif ($this->content[$blockCode]['image_desktop_1']) {
            $a['bg']['src'] = Helper::getAbsoluteUrl($this->content[$blockCode]['image_desktop_1']);

            $a['bg']['disable_lazy'] = true;
        }

        if ($tab = $this->page->getPageImages()['tab']) {
            $a['bg']['tab'] = $tab;
        } elseif ($this->content[$blockCode]['image_tab_1']) {
            $a['bg']['tab']['srcset'][] = [
                'src'   => Helper::getAbsoluteUrl($this->content[$blockCode]['image_tab_1']),
                'scale' => '1'
            ];
        }

        if ($this->content[$blockCode]['image_tab_2']) {
            $a['bg']['tab']['srcset'][] = [
                'src'   => Helper::getAbsoluteUrl($this->content[$blockCode]['image_tab_2']),
                'scale' => '2'
            ];
        }

        if ($mob = $this->page->getPageImages()['mob']) {
            $a['bg']['mob'] = $mob;
        } elseif ($this->content[$blockCode]['image_mob_1']) {
            $a['bg']['mob']['srcset'][] = [
                'src'   => Helper::getAbsoluteUrl($this->content[$blockCode]['image_mob_1']),
                'scale' => '1'
            ];
        }

        if ($this->content[$blockCode]['image_mob_2']) {
            $a['bg']['mob']['srcset'][] = [
                'src'   => Helper::getAbsoluteUrl($this->content[$blockCode]['image_mob_2']),
                'scale' => '2'
            ];
        }

        return $a;
    }

    /**
     * Баннер
     */
    private function getBanner(string $blockCode): array
    {
        $a = [];

        if ($this->content[$blockCode]['text']) {
            $a['text'] = $this->content[$blockCode]['text'];
        }

        return $a;
    }

    /**
     * Новости
     */
    private function getNews(string $blockCode): array
    {
        $a = [];

        $className = "\Bitrix\Iblock\Elements\ElementNews{$this->lang}Table";

        if (!class_exists($className)) {
            return [];
        }

        $elements = $className::getList([
            'filter' => [
                'ACTIVE' => true,
            ],
            'order'  => [
                'SORT'        => 'ASC',
                'ACTIVE_FROM' => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'IBLOCK_SECTION',
                'ACTIVE_FROM',
                'IBLOCK.DETAIL_PAGE_URL',
            ],
            'limit'  => 8,
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        foreach ($elements as $element) {
            $i = [
                'category' => $element->getIblockSection()->getName(),
                'title'    => $element->getName(),
                'href'     => \CIBlock::ReplaceDetailUrl(
                    $element->getIblock()->getDetailPageUrl(),
                    [
                        'LANG_DIR' => ($this->lang === 'ru') ? '' : ('/' . $this->lang),
                        'CODE'     => $element->getCode(),
                    ],
                    false,
                    'E'
                ),
            ];

            if ($element->getActiveFrom() &&
                !empty($this->common['month_' . $element->getActiveFrom()->format('n')])) {
                $i['date'] = $element->getActiveFrom()->format('d ' . $this->common['month_genitive_' . $element->getActiveFrom()->format('n')] . " 'y");
            }

            $a['news'][] = $i;
        }

        if ($a['news']) {
            if ($this->content[$blockCode]['title']) {
                $a['title'] = $this->content[$blockCode]['title'];
            }

            if ($this->content[$blockCode]['all_text']
                && $this->content[$blockCode]['all_link']) {
                $a['link'] = [
                    'text' => $this->content[$blockCode]['all_text'],
                    'href' => $this->content[$blockCode]['all_link'],
                ];
            }
        }

        return $a;
    }
}
