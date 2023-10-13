<?php

namespace Boilerplate\Tools\Auth;

use Boilerplate\Tools\Auth\Containers\AbstractUserContainer;
use Bitrix\Main\Application;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Boilerplate\Tools\Helper;

/**
 * Класс, отвечающий за авторизацию.
 * Содержит методы login, logout, check,
 * работает с контейнерами:
 * AbstractUserContainer - содержит данные о пользователе
 * ErrorCollection - содержит информацию об ошибках
 *
 * TODO register, changePassword
 */
abstract class AbstractAuth
{
	const CONFIRM_METHOD_SMS = 'sms';
	const CONFIRM_METHOD_EMAIL = 'email';
	public $data = [];

	protected AbstractUserContainer $userContainer;
	protected ErrorCollection $errorCollection;

	/**
	 * Создаем контейнер, в котором будут храниться данные для авторизации
	 * Класс AbstractAuth и его наследники содержит только методы, которые работают с AuthContainer и UserContainer
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
		//Если пользователь уже авторизован, заполняем контейнер AbstractUserContainer
		$this->userContainer = $this->setUserDataToContainer();
	}

	/**
	 * Переписывается отдельно для каждой реализации класса
	 * тут содержится проверка, авторизован ли пользователь
	 * если пользователь авторизован, то заполняем $userContainer
	 *
	 * @return AbstractUserContainer
	 */
	abstract protected function setUserDataToContainer() : AbstractUserContainer;

	/**
	 * @return array The authentication container.
	 */
	final public function getUserContainer() : array
	{
		return $this->userContainer->getData();
	}

	/**
	 * Adds an error to the error collection.
	 *
	 * @param Error $error The error to add to the collection.
	 * @return static Returns an instance of the class.
	 */
	public function setError(Error $error) : static
	{
		$this->errorCollection[] = $error;
		return $this;
	}

	/**
	 * Retrieves the errors from the object.
	 *
	 * @return array The errors as an array.
	 */
	public function getErrors() : array
	{
		return $this->errorCollection->toArray();
	}
}