<?php

namespace Boilerplate\Tools\Controller;

use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\ActionFilter\Base;

use Boilerplate\Tools\Auth\AbstractAuth;
use Boilerplate\Tools\Auth\AuthContainer;
use Boilerplate\Tools\Auth\BitrixAuth;
use Boilerplate\Tools\Helper;

class Auth extends Controller
{
	protected AbstractAuth $auth; //способ авторизации

	public function __construct()
	{
		parent::__construct();

		$this->auth = new BitrixAuth();
	}

	public function configureActions(): array
	{
		return [
			'auth' => [
				'prefilters'  => [
					new \Bitrix\Main\Engine\ActionFilter\Csrf(),
				],
				'postfilters' => [],
			],
			'logout' => [
				'prefilters'  => [
					//new \Bitrix\Main\Engine\ActionFilter\Csrf(),
				],
				'postfilters' => [],
			],
			'check' => [
				'prefilters'  => [
					//new \Bitrix\Main\Engine\ActionFilter\Csrf(),
				],
				'postfilters' => [],
			],
			'register' => [
				'prefilters'  => [
					new \Bitrix\Main\Engine\ActionFilter\Csrf(),
				],
				'postfilters' => [],
			],
		];
	}

	/**
	 * Залогиниваемся
	 */
	public function authAction() : array|EventResult
	{
		$auth = &$this->auth;

		$result = $auth->login();
		$errors = $result->getErrors();

		if (!empty($errors)) {
			$this->addErrors($errors);
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return static::getResult($result);
	}

	/**
	 * Разлогиниваемся
	 * @return array|EventResult
	 */
	public function logoutAction() : array|EventResult
	{
		$auth = &$this->auth;

		$result = $auth->logout();
		$errors = $result->getErrors();

		if (!empty($errors)) {
			$this->addErrors($errors);
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return static::getResult($result);
	}

	/**
	 * Проверяем авторизацию
	 * @return array|EventResult
	 */
	public function checkAction() : array|EventResult
	{
		$auth = &$this->auth;

		$result = $auth->check();
		$errors = $result->getErrors();

		if (!empty($errors)) {
			$this->addErrors($errors);
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return static::getResult($result);
	}

	public function registerAction() : array|EventResult
	{
		$auth = &$this->auth;

		$result = $auth->register();
		$errors = $result->getErrors();

		if (!empty($errors)) {
			$this->addErrors($errors);
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return static::getResult($result);
	}

	protected static function getResult(AbstractAuth $result) : array
	{
		$data = [
			'user' => $result->getUserContainer()
		];
		$data = array_merge($result->data, $data);
		$data[Helper::SESSID_CODE] = Helper::getSessid();

		return $data;
	}
}