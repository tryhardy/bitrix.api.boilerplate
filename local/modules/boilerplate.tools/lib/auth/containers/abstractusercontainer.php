<?php
namespace Boilerplate\Tools\Auth\Containers;

/**
 * Класс-обертка для вывода информации о пользователе
 */
abstract class AbstractUserContainer
{
	protected $id = '';
	protected $login = '';
	protected $email = '';
	protected $firstName = '';
	protected $lastName = '';
	protected $token = '';

	protected $user = [];

	/**
	 * Для каждого наследника своя реализация.
	 * Заполняем свойства данными из массива $user
	 * @param array $user
	 */
	abstract public function __construct(array $user = []);

	/**
	 * Выводим готовый массив для отдачи через API
	 * @return array
	 */
	final public function getData() : array
	{
		$result = [];

		if ($this->id || $this->token) {
			$classVars = get_class_vars(get_class($this));
			foreach ($classVars as $name => $value) {
				if ($name === 'user') {
					continue;
				}
				$result[$name] = $this->$name;
			}
		}

		return $result;
	}

	final public function getId() : int|string
	{
		return $this->id;
	}

	final public function getLogin() : string
	{
		return $this->login;
	}

	final public function getLastName() : string
	{
		return $this->lastName;
	}

	final public function getFirstName() : string
	{
		return $this->firstName;
	}

	final public function getFullName() : string
	{
		return $this->fullName;
	}

	final public function getEmail() : string
	{
		return $this->email;
	}

	final public function getToken() : string
	{
		return $this->token;
	}

	final public function getUser() : array
	{
		return $this->user;
	}
}