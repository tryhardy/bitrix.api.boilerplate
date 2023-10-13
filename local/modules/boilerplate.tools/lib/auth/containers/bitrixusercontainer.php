<?php
namespace Boilerplate\Tools\Auth\Containers;

use Boilerplate\Tools\Helper;

/**
 * Класс-контейнер, в котором содержатся все данные о пользователе, заполняемые из битриксовой таблицы b_user
 */
class BitrixUserContainer extends AbstractUserContainer
{
	public function __construct(array $user = [])
	{
		if (!empty($user)) {
			$this->id = $user['ID'] ?: '';
			$this->login = $user['LOGIN'] ?: '';
			$this->email = $user['EMAIL'] ?: '';
			$this->firstName = $user['NAME'] ?: '';
			$this->lastName = $user['LAST_NAME'] ?: '';
		}

		$this->token = '';
		$this->user = $user;
	}
}