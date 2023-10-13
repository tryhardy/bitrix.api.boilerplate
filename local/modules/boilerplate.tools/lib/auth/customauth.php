<?php

namespace Boilerplate\Tools\Auth;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\HttpRequest;
use Boilerplate\Tools\Auth\Containers\CustomUserContainer;
use Boilerplate\Tools\Auth\Interfaces\AuthInterface;
use Boilerplate\Tools\Auth\Interfaces\RegisterInterface;

class CustomAuth extends AbstractAuth implements AuthInterface, RegisterInterface
{
	public function __construct()
	{
		parent::__construct();
	}
	protected function setUserDataToContainer() : CustomUserContainer
	{
		$user = [];
		// Как-нибудь получаем массив с инфой о пользователе
		return new CustomUserContainer($user);
	}

	public function login() : static
	{
		return $this;
	}

	public function check() : static
	{
		return $this;
	}

	public function register() : static
	{
		return $this;
	}
}