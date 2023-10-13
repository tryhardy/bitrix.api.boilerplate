<?php

namespace Boilerplate\Tools\Page;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Boilerplate\Tools\Common\Pages;
use Boilerplate\Tools\Content\Content;

abstract class Page
{
    protected string $code;
    protected array $data;
    public array $content;
    public array $common;
    public $page;

    public function __construct(protected string $lang)
    {
        $this->setLocale();

        $this->code = $this->getCode();

        $this->content = (Content::getInstance())->getContent($this->code, $this->lang);
        $this->common = (Content::getInstance())->getContent('common', $this->lang)['common'];

        $this->page = new Pages($this->lang, $this->getCode());

        Loader::includeModule('iblock');

        $this->data = $this->setData();
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function setLocale()
    {
        if ($context = Context::getCurrent()) {
            $context->setSite($this->lang);
            $context->setLanguage($this->lang);
        }
    }

    abstract protected function getCode(): string;

    abstract protected function setData(): array;
}
