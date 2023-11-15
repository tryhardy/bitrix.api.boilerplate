<?php

namespace Boilerplate\Tools\Page;

use Bitrix\Iblock\SectionTable;
use Boilerplate\Tools\Layout\Menu;
use Uus\Tools\Helper;

class Layout extends Page
{
    protected function getCode(): string
    {
        return 'layout';
    }

	/**
	 * Хедер
	 * @param string $blockCode
	 * @return array
	 */
    protected function getHeader(string $blockCode): array
    {
		$menu = new Menu($this->lang);

        $a = [
            'nav' => $menu->get('header'),
	        'hamburger' => $menu->get('hamburger'),
        ];

        return $a;
    }

    /**
     * Футер
     */
    protected function getFooter(string $blockCode): array
    {
        $a = [];

        return $a;
    }
}
