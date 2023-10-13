<?php
namespace Boilerplate\Tools\Auth\Interfaces;
use Boilerplate\Tools\Auth\AuthContainer;
interface LogoutInterface
{
	public function logout() : static;
}