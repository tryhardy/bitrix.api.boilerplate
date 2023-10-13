<?php

namespace Boilerplate\Tools\TextPage;

use Bitrix\Main\Loader;

abstract class TextPage
{
    public array $data;

    public function __construct(
        protected string $lang,
        protected string $page = ''
    ) {
        $this->pageClassName = "\Bitrix\Iblock\Elements\ElementTextPages{$this->lang}Table";

        Loader::includeModule('iblock');

        $this->data = $this->getPageData();
    }

    abstract protected function getCode(): string;

    abstract protected function setData(): array;

    protected function getPageData(): array
    {
        if (!class_exists($this->pageClassName)) {
            return [];
        }

        $pageData = $this->pageClassName::getList([
            'filter' => [
                'ACTIVE' => true,
                'CODE'   => $this->getCode(),
            ],
            'order'  => [
                'SORT' => 'ASC',
                'ID'   => 'DESC',
            ],
            'select' => [
                'ID',
                'CODE',
                'NAME',
                'PREVIEW_TEXT',
                'DETAIL_TEXT',
            ],
            'limit'  => 1,
            'cache'  => [
                'ttl' => 600000,// около недели (в секундах)
            ],
        ]);

        while ($page = $pageData->fetch()) {
            $a = [
                'title'   => $page->get('NAME'),
                'h1'      => $page->get('PREVIEW_TEXT'),
                'content' => $page->get('DETAIL_TEXT'),
            ];
        }
        return $a;
    }
}
