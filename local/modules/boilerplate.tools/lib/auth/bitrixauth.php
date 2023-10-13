<?php

namespace Boilerplate\Tools\Auth;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\HttpRequest;
use Boilerplate\Tools\Auth\Interfaces\AuthInterface;
use Boilerplate\Tools\Auth\Interfaces\LogoutInterface;
use Boilerplate\Tools\Auth\Containers\BitrixUserContainer;
use Boilerplate\Tools\Auth\Interfaces\RegisterInterface;
use Boilerplate\Tools\Auth\Traits\BitrixTrait;
use Boilerplate\Tools\Helper;

class BitrixAuth extends AbstractAuth implements AuthInterface, LogoutInterface, RegisterInterface
{
	const ERROR_CODE_LOGIN = 'LOGIN_ERROR';
	const ERROR_CODE_LOGOUT = 'LOGOUT_ERROR';
	const ERROR_CODE_REGISTER = 'REGISTER_ERROR';
	const ERROR_EXECUTE_EVENT = 'EXECUTE_EVENT_ERROR';
	CONST ERROR_SMS_SEND = 'SMS_SEND_ERROR';

	const ERROR_CODE_LOGIN_MESSAGE = 'User is already authorized';
	const ERROR_CODE_LOGIN_EMPTY_PASS_MESSAGE = 'Empty login or password';
	const ERROR_CODE_LOGIN_WRONG_PASS_MESSAGE = 'Wrong login or password';
	const ERROR_CODE_LOGOUT_MESSAGE = 'User is not authorized';
	const ERROR_CODE_REGISTER_BLOCK_MESSAGE = 'User registration is blocked';
	const ERROR_CODE_REGISTER_FIELD_REQUIRED_MESSAGE = 'Field "#FIELD#" is required';

	const SUCCESS_LOGIN_MESSAGE = 'User authorized';
	const SUCCESS_LOGOUT_MESSAGE = 'User is logged out';
	const SUCCESS_REGISTER_MESSAGE = 'User is created';

	/**
	 * Метод вызывается в __construct и заполняет массив с данными пользователя
	 * если тот авторизован
	 * @return BitrixUserContainer
	 */
	protected function setUserDataToContainer() : BitrixUserContainer
	{
		global $USER;
		$userId = $USER->GetID();
		$user = [];

		if ($userId > 0) {
			$user = $USER->GetById($userId)->Fetch();
		}

		return new BitrixUserContainer($user);
	}

	public function login() : static
	{
		// Добавляем ошибку, если пользователь уже авторизован
		if ($this->userContainer->getId()) {
			$this->setError(new Error(static::ERROR_CODE_LOGIN_MESSAGE, static::ERROR_CODE_LOGIN));
			return $this;
		}

		// Проверям, чтобы логин и пароль были в запросе или добавляем ошибку
		$request = Application::getInstance()->getContext()->getRequest();
		$isPost = $request->isPost();
		if ($isPost) $params = $request->getPostList()->getValues();
		else $params = $request->getQueryList()->getValues();
		if (!$params['login'] || !$params['password']) {
			$this->setError(new Error(static::ERROR_CODE_LOGIN_EMPTY_PASS_MESSAGE, static::ERROR_CODE_LOGIN));
			return $this;
		}

		// Авторизуем
		global $USER;
		$arAuthResult = $USER->Login($request->get('login'), $request->get('password'));

		if ($arAuthResult['TYPE'] !== 'ERROR') {
			$this->userContainer = $this->setUserDataToContainer();
			$this->data['message'] = static::SUCCESS_LOGIN_MESSAGE;
		}
		else {
			// Если логин или пароль неправильные, то добавляем ошибку
			$this->setError(new Error(static::ERROR_CODE_LOGIN_WRONG_PASS_MESSAGE, static::ERROR_CODE_LOGIN));
		}

		return $this;
	}

	public function logout() : static
	{
		global $USER;

		if ($USER->IsAuthorized()) {
			$USER->Logout();
			$this->userContainer = $this->setUserDataToContainer();
			$this->data['message'] = static::SUCCESS_LOGOUT_MESSAGE;
		}
		else {
			$this->setError(new Error(static::ERROR_CODE_LOGOUT_MESSAGE, static::ERROR_CODE_LOGOUT));
		}

		return $this;
	}

	public function check() : static
	{
		return $this;
	}

	public function register() : static
	{
		global $APPLICATION;
		global $USER;
		global $DB;

		//Авторизовать пользователя сразу после регистрации (будет работать, если не требуется подтверждения по email/sms)
		$authAfterRegister = true;

		$result = [];
		$request = Application::getInstance()->getContext()->getRequest();
		$eventManager = \Bitrix\Main\EventManager::getInstance();

		//Если регистрация пользователя запрещена в Главном модуле - возвращаем ошибку
		$allowUserRegistration = Option::get('main', 'new_user_registration');
		if ($allowUserRegistration !== 'Y') {
			$this->setError(new Error(static::ERROR_CODE_REGISTER_BLOCK_MESSAGE, static::ERROR_CODE_REGISTER));
		}

		//Формируем массив с настройками из Главного модуля
		$options = $this->setOptions();
		//Формируем массив с дефолтными полями исходя из настроек в Главном модуле
		$result['default_fields'] = $this->setDefaultFields($options);

		//Использовать капчу при регистрации
		$result['use_captcha'] = Option::get('main', 'captcha_registration', 'N') == 'Y' ? 'Y' : 'N';

		//Регистрация пользователя происходит только если пользователь не авторизован в системе
		if (!$this->userContainer->getId()) {

			//Тут прописываем правило проверки для шифрованного пароля
			if (Option::get('main', 'use_encrypted_auth', 'N') == 'Y') {
				//TODO check encrypted user password
			}

			//Проверка полей на пустоту
			foreach ($result['default_fields'] as $key) {
				$result['values'][$key] = $request->get(strtolower($key));
				if (trim($result['values'][$key]) == '') {
					$message = str_replace("#FIELD#", strtolower($key), static::ERROR_CODE_REGISTER_FIELD_REQUIRED_MESSAGE);
					$this->setError(new Error($message, static::ERROR_CODE_REGISTER . '_FIELD_' . $key));
				}
			}

			//Проверка капчи
			if ($result['use_captcha'] == 'Y') {
				if (!$APPLICATION->CaptchaCheckCode($request->get('captcha_word'), $request->get('captcha_sid'))) {
					$this->setError(new Error('Wrong captcha', 'AUTH_ERROR_CAPTCHA'));
				}
			}

			//Если есть ошибки и в настройках Главного модуля указано их логировать - логируем их
			$errors = $this->getErrors();
			if (!empty($errors)) {
				if (Option::get('main', 'event_log_register_fail', 'N') === 'Y') {
					$logErrors = [];
					foreach($errors as $error) {
						$key = $error->getCode();
						if(intval($key) == 0 && $key !== 0) {
							$logErrors[$key] = $error->getMessage();
						}
					}
					\CEventLog::Log('SECURITY', 'USER_REGISTER_FAIL', 'boilerplate.tools', false, implode('<br>', $logErrors));
				}
			}
			//Если ошибок нет - продолжаем регистрацию
			else {
				$result['values']['GROUP_ID'] = [];

				//Группа, в которую попадает новый пользователь
				$defGroup = Option::get('main', 'new_user_registration_def_group', '');
				if($defGroup != '') {
					$result['values']['GROUP_ID'] = explode(',', $defGroup);
				}

				//Нуждно для проставления флага активности пользователя
				$bConfirmReq = ($options['use_email_confirmation'] === 'Y');
				$active = ($bConfirmReq || $options['phone_required'] ? 'N': 'Y');

				$result['values']['CHECKWORD'] = \Bitrix\Main\Security\Random::getString(32);
				$result['values']['~CHECKWORD_TIME'] = $DB->CurrentTimeFunction();
				$result['values']['ACTIVE'] = $active;
				$result['values']['CONFIRM_CODE'] = ($bConfirmReq ? \Bitrix\Main\Security\Random::getString(8) : '');
				$result['values']['LID'] = SITE_ID;
				$result['values']['LANGUAGE_ID'] = LANGUAGE_ID;
				$result['values']['USER_IP'] = $_SERVER['REMOTE_ADDR'];
				$result['values']['USER_HOST'] = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
				if ($result['values']['AUTO_TIME_ZONE'] <> 'Y' && $result['values']['AUTO_TIME_ZONE'] <> 'N') {
					$result['values']['AUTO_TIME_ZONE'] = '';
				}

				//Тут срабатывают обработкичики событий OnBeforeUserRegister
				//Если хоть один из них возвращает ошибку - прекращаем регистрацию
				//TODO ExecuteModuleEventEx - метод старого ядра, который еще пока что работает
				$eventChecker = true;
				$events = $eventManager->findEventHandlers('main', 'OnBeforeUserRegister');
				foreach ($events as $event) {
					if (ExecuteModuleEventEx($event, [&$result['values']]) === false) {
						if ($err = $APPLICATION->GetException()) {
							$errors = explode('<br>', $err->GetString());
							foreach ($errors as $error) {
								$this->setError(new Error($error, STATIC::ERROR_EXECUTE_EVENT));
							}
						}
						$eventChecker = false;
						break;
					}
				}

				$ID = 0;
				$user = new \CUser();
				if ($eventChecker) {
					$ID = $user->Add($result['values']);
				}

				if (intval($ID) > 0) {
					if ($options['phone_registration'] && $result['values']["PHONE_NUMBER"] <> '') {
						//added the phone number for the user, now sending a confirmation SMS
						list($code, $phoneNumber) = \CUser::GeneratePhoneCode($ID);

						$sms = new \Bitrix\Main\Sms\Event(
							'SMS_USER_CONFIRM_NUMBER',
							[
								'USER_PHONE' => $phoneNumber,
								'CODE' => $code,
							]
						);

						$smsResult = $sms->send(true);

						if (!$smsResult->isSuccess()) {
							$smsErrorMessages = $smsResult->getErrorMessages();
							foreach($smsErrorMessages as $smsErrorMessage) {
								$this->setError(new Error($smsErrorMessage, static::ERROR_SMS_SEND));
							}
						}
						else {
							//Если включено подтверждение по СМС, то на выходе фронт получает код
							$this->data['user_id'] = $ID;
							$this->data['confirm'] = $options['phone_registration'];
							if ($this->data['confirm']) {
								$this->data['method'] = static::CONFIRM_METHOD_SMS;
							}
							$this->data['signed_data'] = \Bitrix\Main\Controller\PhoneAuth::signData(['phoneNumber' => $phoneNumber]);
						}
					}
					else {
						if ($authAfterRegister && $result['values']['ACTIVE'] == 'Y') {
							if (!$arAuthResult = $USER->Login($result['values']['LOGIN'], $result['values']['PASSWORD'])) {
								$this->setError(new Error($arAuthResult, static::ERROR_CODE_LOGIN));
							}
							else {
								$this->userContainer = $this->setUserDataToContainer();
							}
						}

						$this->data['user_id'] = $ID;
						if ($bConfirmReq) {
							$this->data['confirm'] = $bConfirmReq;
							$this->data['method'] = static::CONFIRM_METHOD_EMAIL;
						}
					}

					$result['values']["USER_ID"] = $ID;

					$arEventFields = $result['values'];
					unset($arEventFields["PASSWORD"]);
					unset($arEventFields["CONFIRM_PASSWORD"]);

					//Отправляем письмо с подтверждением или уведомлением о регистрации
					$event = new \CEvent;
					$event->SendImmediate('NEW_USER', SITE_ID, $arEventFields);
					if($bConfirmReq) {
						$event->SendImmediate('NEW_USER_CONFIRM', SITE_ID, $arEventFields);
					}
				}
				else {
					$this->setError(new Error(trim($user->LAST_ERROR), static::ERROR_CODE_REGISTER));
				}

				//Логируем успешное завершение регистрации, если такая настройка установлена
				$errors = $this->getErrors();
				if (empty($errors)) {
					if(Option::get('main', 'event_log_register', 'N') === 'Y') {
						\CEventLog::Log('SECURITY', 'USER_REGISTER', 'boilerplate.tools', $ID);
					}
				}
				//Логируем ошибку регистрации, если такая настройка установлена
				else {
					$logErrors = [];
					foreach($errors as $error) {
						$logErrors[] = $error->getMessage();
					}

					if(Option::get('main', 'event_log_register_fail', 'N') === 'Y') {
						\CEventLog::Log('SECURITY', 'USER_REGISTER_FAIL', 'boilerplate.tools', $ID, implode('<br>', $logErrors));
					}
				}

				$events = $eventManager->findEventHandlers('main', 'OnAfterUserRegister');
				foreach ($events as $arEvent) {
					ExecuteModuleEventEx($arEvent, [&$result['values']]);
				}
			}
		}
		else {
			$this->setError(new Error('You have been already authorized', 'AUTH_ERROR'));
		}

		return $this;
	}

	/**
	 * Sets the options for method register from main module
	 *
	 * @return array The options for the class.
	 */
	private function setOptions()
	{
		$options = [];
		$options['phone_registration'] = (Option::get('main', 'new_user_phone_auth', 'N') == 'Y');
		$options['phone_required'] = ($options['phone_registration'] && Option::get('main', 'new_user_phone_required', 'N') == 'Y');
		$options['email_registration'] = (Option::get('main', "new_user_email_auth", 'Y') <> 'N');
		$options['email_required'] = ($options['email_registration'] && Option::get('main', 'new_user_email_required', 'Y') <> 'N');
		$options['use_email_confirmation'] = (Option::get('main', 'new_user_registration_email_confirmation', 'N') == 'Y' && $options['email_required'] ? 'Y' : 'N');
		$options['phone_code_resend_interval'] = \CUser::PHONE_CODE_RESEND_INTERVAL;
		return $options;
	}

	/**
	 * Sets the default fields for the given options for method register.
	 *
	 * @param array $options An array of options.
	 * @return array The default fields.
	 */
	private function setDefaultFields($options = [])
	{
		// apply core fields to user defined
		$defaultFields = [
			'LOGIN'
		];

		if ($options['email_required']) {
			$defaultFields[] = 'EMAIL';
		}

		if ($options['phone_required']) {
			$defaultFields[] = 'PHONE_NUMBER';
		}

		$defaultFields[] = 'PASSWORD';

		return $defaultFields;
	}
}