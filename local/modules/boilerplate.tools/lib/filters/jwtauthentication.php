<?php

namespace Boilerplate\Tools\Filters;

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\ActionFilter\Authentication as BitrixAuthentication;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTAuthentication extends Base
{
	const ERROR_INVALID_AUTHENTICATION = 'invalid_authentication';
	const ERROR_EMPTY_JWT_KEY = 'empty_jwt_key';

	private $enableRedirect;
	private $JWTKey;

	/**
	 * @throws Exception
	 */
	public static function getInstance($enableRedirect = false)
	{
		if (class_exists('\Firebase\JWT\JWT') && class_exists('\Firebase\JWT\Key')) {
			return new static($enableRedirect);
		}
		else {
			return new BitrixAuthentication($enableRedirect);
		}
	}

	/**
	 * @throws Exception
	 */
	protected function __construct($enableRedirect = false)
	{
		$this->enableRedirect = $enableRedirect;
		parent::__construct();
	}

	public function onBeforeAction(Event $event)
	{
		global $USER;

		$JWTKey = \Bitrix\Main\Config\Option::get("boilerplate.tools", "JWT_KEY");
		if (!$JWTKey) {
			$this->addError(new Error(
				"Empty JWT Key", self::ERROR_EMPTY_JWT_KEY)
			);
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

//		if (!($USER instanceof \CAllUser) || !$USER->getId())
//		{
//			$isAjax = $this->getAction()->getController()->getRequest()->getHeader('BX-Ajax');
//			if ($this->enableRedirect && !$isAjax)
//			{
//				LocalRedirect(
//					SITE_DIR .
//					'auth/?backurl=' .
//					urlencode(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri())
//				);
//
//				return new EventResult(EventResult::ERROR, null, null, $this);
//			}
//
//			Context::getCurrent()->getResponse()->setStatus(401);
//			$this->addError(new Error(
//					Loc::getMessage("MAIN_ENGINE_FILTER_AUTHENTICATION_ERROR"), self::ERROR_INVALID_AUTHENTICATION)
//			);
//
//			return new EventResult(EventResult::ERROR, null, null, $this);
//		}

		return null;
	}
}