<?php

namespace Boilerplate\Tools\Model;

use Bitrix\Main\Type\DateTime as BitrixDateTime;
use Boilerplate\Tools\Content\Content;
use Bitrix\Main\Loader;
use Boilerplate\Tools\Helper;

class News
{
    public array $content;
    public array $common;
    protected array $data;

    public function __construct(
        protected int $limit = 0,
        protected int $offset = 0,
        protected string $lang = ''
    ) {
        $this->content = (Content::getInstance())->getContent('news', $this->lang);
        $this->common = (Content::getInstance())->getContent('common', $this->lang)['common'];

        $this->data = $this->setData();
    }

    public function getData(): array
    {
        return $this->data;
    }

    protected function setData(): array
    {
        Loader::includeModule('iblock');

        $this->page = new Pages($this->lang, 'news');

        return $this->getNews();
    }

    protected function getNews(): array
    {
        $a = [];

        return $a;
    }
}
