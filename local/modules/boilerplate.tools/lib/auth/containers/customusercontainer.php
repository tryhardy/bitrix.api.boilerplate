<?php
namespace Boilerplate\Tools\Auth\Containers;

use Boilerplate\Tools\Helper;

/**
 * Класс-контейнер, в котором содержатся все данные о пользователе, заполняемые из битриксовой таблицы b_user
 */
class CustomUserContainer extends AbstractUserContainer
{
	public function __construct(array $user = [])
	{
		//TODO заполняем контейнер с пользователем
	}
}