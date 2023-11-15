<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Boilerplate\Tools\Controller;

return function (RoutingConfigurator $routes) {
    $routes->prefix('api')
        ->group(function (RoutingConfigurator $routes) {
	        // Скрипты
	        $routes->get('links/js', [Controller\Links::class, 'getJs']);
	        $routes->options('links/js', [Controller\Preflight::class, 'preflight']);

            // Контент страниц
            $routes->get('pages/{page}', [Controller\Page::class, 'get']);
            $routes->options('pages/{page}', [Controller\Preflight::class, 'preflight']);

            // Контент текстовых страниц
            $routes->get('textpage/{page}', [Controller\TextPage::class, 'get']);
            $routes->options('textpage/{page}', [Controller\Preflight::class, 'preflight']);

            // Формы
            $routes->post('forms/{form}', [Controller\Form::class, 'write']);
            $routes->options('forms/{form}', [Controller\Preflight::class, 'preflight']);

            // Поиск
            $routes->get('search', [Controller\Search::class, 'get']);
            $routes->options('search', [Controller\Preflight::class, 'preflight']);

            // Новости
            $routes->get('news', [Controller\News::class, 'get']);
            $routes->options('news', [Controller\Preflight::class, 'preflight']);

            // Детальная новост
            $routes->get('news/{news}', [Controller\NewsDetail::class, 'get']);
            $routes->options('news/{news}', [Controller\Preflight::class, 'preflight']);

	        /**
	         * AUTH SECTION
	         * TODO should be moved to auth.php
	         */
	        $authAccept = \Bitrix\Main\Config\Option::get('boilerplate.tools', 'ACCEPT_AUTH');
	        if ($authAccept === 'Y') {
		        //Авторизация
		        $routes->post('auth/login', [Controller\Auth::class, 'auth']);
		        $routes->options('auth/login', [Controller\Preflight::class, 'preflight']);

		        //Разлогиниваем
		        $routes->post('auth/logout', [Controller\Auth::class, 'logout']);
		        $routes->options('auth/logout', [Controller\Preflight::class, 'preflight']);

		        //Проверка авторизации
		        $routes->post('auth/check', [Controller\Auth::class, 'check']);
		        $routes->options('auth/check', [Controller\Preflight::class, 'preflight']);

		        //Регистрация
		        $routes->post('auth/register', [Controller\Auth::class, 'register']);
		        $routes->options('auth/register', [Controller\Preflight::class, 'preflight']);

		        //TODO forgot password
		        //TODO update password
		        //TODO update profile
	        }
        });
};
