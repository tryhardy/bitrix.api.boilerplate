<?php

namespace Boilerplate\Tools\Controller;

use Boilerplate\Tools\Helper;

class Session extends Controller
{

	public function configureActions(): array
	{
		return [
			'get' => [
				'prefilters'  => [],
				'postfilters' => [],
			],
		];
	}

	/**
	 * @return array The array containing the session ID code.
	 */
	public function getAction() : array
	{
		return [
			Helper::SESSID_CODE => Helper::getSessid(),
		];
	}
}