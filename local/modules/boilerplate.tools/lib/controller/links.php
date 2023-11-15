<?php

namespace Boilerplate\Tools\Controller;
ц
class Links extends Controller
{
	const LIBS = [
		'core' => '/bitrix/js/main/core/core.min.js'
	];
	public function configureActions(): array
	{
		return [
			'getJs' => [
				'prefilters'  => [],
				'postfilters' => [],
			],
		];
	}

	public function getJsAction(array $libs = ['core'])
	{
		$result = [];

		foreach($libs as $lib) {
			if ($link = static::LIBS[$lib]) {
				$result[$lib] = $link;
			}
		}
		return $result;
	}
}