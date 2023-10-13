<?php
namespace Boilerplate\Tools\Auth\Interfaces;

/**
 * Базовый интерфейс для класса авторизации.
 */
interface AuthInterface
{
	public function login() : static;

	public function check() : static;
}