<?php

namespace Boilerplate\Tools\Model;

use Bitrix\Main\Loader;
use Boilerplate\Tools\Content\Content;
use Boilerplate\Tools\Helper;

class Pages
{
	public const PAGES_ENTITY_CLASS = '\Bitrix\Iblock\Elements\ElementPages#LANG#Table';
    private readonly string $className;
    public int $pagesIblockId;
    private array $breadcrumbs;
    private array $meta;
    private array $images;
    private string $mask;
    private string $describe;
    private array $describeLinkList;

    public function __construct(private readonly string $lang, private readonly string $page = '')
    {
        Loader::includeModule('iblock');

        $this->className = str_replace('#LANG#', $this->lang, self::PAGES_ENTITY_CLASS);

        if ($this->page) {
            $this->getPageData($this->page);
        }

        $this->common = (Content::getInstance())->getContent('common', $this->lang)['common'];
    }

    /**
     * Получает данные страницы
     */
    protected function getPageData(string $pageCode): bool
    {
        $this->breadcrumbs = [];
        $this->meta = [];
        $this->images = [];
        $this->mask = '';
        $this->describe = '';
        $this->describeLinkList = [];

        if (!class_exists($this->className)) {
            return false;
        }

        $pages = $this->className::getList([
            'filter' => [
                'ACTIVE' => true,
                'CODE'   => $pageCode,
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'IBLOCK_ID',
                'BREADCRUMBS',
                'SEO_TITLE',
                'SEO_H1',
                'SEO_DESCRIPTION',
                'LINK',
                'MASK_TYPE.ITEM',
                'PREVIEW_PICTURE',
                'DETAIL_PICTURE',
                'IMAGE_MOB',
                'PREVIEW_TEXT',
                'DESCRIBE_LINK',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        foreach ($pages as $page) {
            // Хлебные крошки
            $breadCrumbs = $page->getBreadcrumbs()->getAll();

            foreach ($breadCrumbs as $i => $breadCrumb) {
                $b = [
                    'text' => $breadCrumb?->getValue(),
                ];

                if ($href = $breadCrumb->getDescription()) {
                    $b['href'] = $href;
                } elseif (((is_countable($breadCrumbs) ? count($breadCrumbs) : 0) - 1) === $i) {
                    $b['current'] = true;
                }

                $this->breadcrumbs[] = $b;
            }

            // Meta
            if ($title = $page?->getSeoTitle()?->getValue()) {
                $this->meta['pageTitle'] = $title;
            }

            if ($h1 = $page?->getSeoH1()?->getValue()) {
                $this->meta['pageH1'] = $h1;
            }

            if ($description = $page?->getSeoDescription()?->getValue()) {
                $this->meta['pageDescription'] = $description;
            }

            // Тип маски для изображения
            if ($page?->getMaskType()->getItem() && ($maskType = $page?->getMaskType()->getItem()->getXmlId())) {
                $this->mask = $maskType;
            }

            // изображения для шапки страницы
            if ($imageDesktop = $page?->getPreviewPicture()) {
                $this->images['src'] = Helper::getAbsoluteUrl(\CFile::GetPath($imageDesktop));
            }
            if ($imageTab = $page?->getDetailPicture()) {
                $this->images['tab']['srcset'][] = [
                    'src'   => Helper::getAbsoluteUrl(\CFile::GetPath($imageTab)),
                    'scale' => 1,
                ];
            }
            if ($page->getImageMob() && ($imageMob = $page?->getImageMob()?->getValue())) {
                $this->images['mob']['srcset'][] = [
                    'src'   => Helper::getAbsoluteUrl(\CFile::GetPath($imageMob)),
                    'scale' => 1,
                ];
            }

            // краткое описание под заголовком
            if ($describe = $page->getPreviewText()) {
                $this->describe = $describe;
            }

            // ссылки под кратким описанием
            if ($page?->getDescribeLink() && ($descLink = $page?->getDescribeLink()?->getAll())) {
                foreach ($descLink as $link) {
                    if (($href = $link?->getValue()) && ($text = $link?->getDescription())) {
                        $this->describeLinkList[] = [
                            'text' => $text,
                            'href' => $href,
                        ];
                    }
                }
            }
        }

        return true;
    }

    /**
     * Возвращает список статических страниц
     */
    public function getStaticPages(): array
    {
        $a = [];

        if (!class_exists($this->className)) {
            return $a;
        }

        $pages = $this->className::getList([
            'filter' => [
                'ACTIVE' => true,
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'IBLOCK_ID',
                'LINK',
                'BREADCRUMBS',
            ],
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ])->fetchCollection();

        foreach ($pages as $page) {
            $p = [
                'ID'   => $page->getId(),
                'LINK' => $page->getLink()?->getValue(),
            ];

            if ($page->getIblockId()) {
                $this->pagesIblockId = $page->getIblockId();
            }

            $breadCrumbs = $page->getBreadcrumbs()->getAll();

            foreach ($breadCrumbs as $breadCrumb) {
                $p['BREADCRUMBS'][] = $breadCrumb?->getValue();
            }

            $a[$page->getId()] = $p;
        }

        return $a;
    }

    /**
     * Возвращает хлебные крошки для страницы
     */
    public function getPageBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    /**
     * Возвращает мета-теги для страницы
     */
    public function getPageMeta(): array
    {
        return $this->meta;
    }

    /**
     * Возвращает мета-теги для страницы
     */
    public function getPageMaskType(): string
    {
        return $this->mask;
    }

    /**
     * Возвращает изобращения для шапки
     */
    public function getPageImages(): array
    {
        return $this->images;
    }

    /**
     * Возвращает краткое описание под заголовком
     */
    public function getDescribe(): string
    {
        return $this->describe;
    }

    /**
     * Возвращает ссылки под кратким описанием
     */
    public function getDescribeLinkList(): array
    {
        return $this->describeLinkList;
    }
}
