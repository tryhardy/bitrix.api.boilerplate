<?php

namespace Boilerplate\Tools\Content;

class Content
{
    private static $instance;
    private array $content;

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * @return mixed|static
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getContent(string $pageCode, string $languageId)
    {
        if (!empty($this->content[$pageCode])) {
            return $this->content[$pageCode];
        }

        $this->content[$pageCode] = ContentTable::getPageContent($pageCode, $languageId);
        return $this->content[$pageCode];
    }
}
