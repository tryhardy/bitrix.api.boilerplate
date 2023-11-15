<?php

namespace Boilerplate\Tools\Layout;

use Boilerplate\Tools\Model\Pages;
use Boilerplate\Tools\Traits\Locale;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\ElementTable;

class Menu
{
	protected const CACHE_TIME = 86400;
	protected static int $depthLevel = 5;

	use Locale;

	public function __construct(protected string $lang)
	{
		$this->setLocale($this->lang);
	}

	/**
	 * Пример:
	 * $menu = new Menu($lang);
	 * $headerMenu = $menu->get('header');
	 * $footerMenu = $menu->get('footer');
	 *
	 * @param string $code
	 * @return array
	 */
	public function get(string $code): array
	{
		$apiCode = $code . $this->lang;

		$a = [];

		$entity = \Bitrix\Iblock\Model\Section::compileEntityByIblock($apiCode);
		if (!$entity) return $a;

		$sections = $entity::getList([
			'filter' => [
				'ACTIVE'    => true,
			],
			'order'  => [
				'SORT'      => 'ASC',
				'ID'        => 'DESC',
			],
			'select'        => [
				'ID',
				'NAME',
				'DESCRIPTION',
				'IBLOCK_SECTION_ID',
				'DEPTH_LEVEL',
				'UF_LINK'
			],
			'cache'         => [
				'ttl'       => static::CACHE_TIME,// около недели (в секундах)
			],
		])->fetchCollection();

		/*
		 * Получим список разделов
		 */
		$sectionList = [];
		$arLinks = [];
		$arLinksId = [];

		foreach ($sections as $section) {
			$link = $section->get('UF_LINK');
			if ($link) $arLinksId[$section->getId()] = $link;

			$sectionList[$section->getId()] = [
				'ID'                => $section->getId(),
				'NAME'              => $section->getName(),
				'IBLOCK_SECTION_ID' => $section->get('IBLOCK_SECTION_ID'),
				'DESCRIPTION'       => $section->getDescription(),
				'DEPTH_LEVEL'       => $section->get('DEPTH_LEVEL'),
			];
		}

		if (!empty($arLinksId)) {
			$arLinks = $this->getLinks($arLinksId);
		}

		if (!empty($arLinks)) {
			foreach ($sectionList as &$sectionItem) {
				if ($val = $arLinks[$sectionItem['ID']]) {
					$sectionItem['DESCRIPTION'] = $val;
				}
			}
		}

		for ($depthLevel = static::$depthLevel; $depthLevel > 0; $depthLevel--) {
			foreach ($sectionList as $sectionId => $sectionValue) {
				if ($sectionValue['DEPTH_LEVEL'] === $depthLevel && $sectionValue['IBLOCK_SECTION_ID']) {
					$sectionList[$sectionValue['IBLOCK_SECTION_ID']]['ITEMS'][] = $sectionValue;
					unset($sectionList[$sectionId]);
				}
			}
		}

		/*
		 * Зададим данные
		 */
		foreach ($sectionList as $section) {
			$s = [
				'text' => $section['NAME'],
			];

			if ($section['DESCRIPTION']) {
				$s['href'] = $section['DESCRIPTION'];
			}

			if ($items = $section['ITEMS']) {
				$s['subnav'] = $this->getSubNav($items) ?: [];
			}

			$a[] = $s;
		}

		return $a;
	}

	/**
	 * Рекурсивная функция для формирования подменю
	 * @param $items
	 * @return array
	 */
	private function getSubNav($items) : array
	{
		$menu = [];

		foreach ($items as $item) {
			$ss = [
				'text' => $item['NAME'],
			];

			if ($item['DESCRIPTION']) {
				$ss['href'] = $item['DESCRIPTION'];
			}

			if ($item['ITEMS']) {
				$ss['subnav'] = $this->getSubNav($item['ITEMS']) ?: [];
			}

			$menu[] = $ss;
		}

		return $menu;
	}

	/**
	 * Получаем ссылки на страницы из инфоблока "Страницы"
	 * @param array $ids
	 * @return array
	 */
	private function getLinks(array $ids): array
	{
		if (empty($ids)) return $ids;

		$entity = str_replace('#LANG#', $this->lang, Pages::PAGES_ENTITY_CLASS);

		$arLinks = [];
		$elements = $entity::getList([
			'filter' => [
				'ACTIVE'        => true,
				'ID'            => $ids,
				'!LINK_VALUE'    => null
			],
			'order'  => [
				'SORT' => 'ASC',
				'ID'   => 'DESC',
			],
			'select' => [
				'ID',
				'NAME',
				'LINK_' => 'LINK'
			],
			'cache'  => [
				'ttl' => static::CACHE_TIME,// около недели (в секундах)
			],
		])->fetchAll();

		foreach($elements as $element) {
			$arLinks[$element['ID']] = $element['LINK_VALUE'];
		}

		foreach($ids as &$id) {
			if (isset($arLinks[$id])) {
				$id = $arLinks[$id];
			}
		}

		return $ids;
	}
}