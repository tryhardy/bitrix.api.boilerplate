<?php

namespace Boilerplate\Tools\Traits;

use Bitrix\Main\Context;

trait Locale
{
	protected function setLocale($lang)
	{
		if ($context = Context::getCurrent()) {
			$context->setSite($lang);
			$context->setLanguage($lang);
		}
	}
}