<?php

namespace Boilerplate\Tools\Page;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Boilerplate\Tools\Content\Content;
use Boilerplate\Tools\Model\Pages;
use Boilerplate\Tools\Traits\Locale;
use ReflectionException;

abstract class Page
{
	protected const EXCLUDE_METHODS = ['getData', 'getClassMethods'];

    protected string $code;
    protected PageObject $data;
    public array $content;
    public array $common;
    public $page;

	use Locale;

    public function __construct(protected string $lang)
    {
        $this->setLocale($this->lang);

        $this->code = $this->getCode();
        $this->content = (Content::getInstance())->getContent($this->code, $this->lang) ?: [];

	    $common = (Content::getInstance())->getContent('common', $this->lang)['common'] ?: [];
	    $this->common = $common;

	    $this->page = new Pages($this->lang, $this->getCode()) ?: [];

        Loader::includeModule('iblock');

        $this->data = $this->setData() ?: [];
    }

    public function getData(): PageObject
    {
        return $this->data;
    }

    abstract protected function getCode(): string;

	/**
	 * Получает все public/protected методы класса (наследника), в названии которых есть get,
	 * кроме методов, включенных в static::EXCLUDE_METHODS
	 * @return PageObject
	 * @throws ReflectionException
	 */
    protected function setData() : PageObject
    {
	    $object = new PageObject();
	    $classMethods = $this->getClassMethods();

	    if (!empty($classMethods)) {
		    foreach ($classMethods as $method) {
			    $methodCode = strtolower(substr($method, 3));
			    $object->$methodCode = $this->$method($methodCode);
		    }
	    }

	    return $object;
    }

	/**
	 * @throws ReflectionException
	 */
	protected function getClassMethods() : array
	{
		$result = [];
		$class = new \ReflectionClass($this::class);
		$classMethods = $class->getMethods();

		foreach($classMethods as $method) {
			$methodName = $method->name;

			if (!str_starts_with($methodName, 'get') || in_array($methodName, static::EXCLUDE_METHODS)) {
				continue;
			}
			$result[] = $method->name;
		}

		return $result;
	}
}
