# bitrix.api.boilerplate
 
Бойлерплейт для работы с битриксом через роутинг  
[Инструкция по настройке битры тут](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=013764)  

## CSRF filters
В **GET** передавать sessid={SESSION_ID}  
Либо в **HEADER** 'X-Bitrix-Csrf-Token {SESSION_ID}'  
В коде контроллера включать через prefilters в методе configureActions
```php
public function configureActions(): array
{
    return [
        'write' => [
            'prefilters'  => [
                //checking sessid param in request
                new \Bitrix\Main\Engine\ActionFilter\Csrf(),
            ],
            'postfilters' => [],
        ],
    ];
}
```

### Получить ID сессии   
**GET** /api/sessid  
Response:
```json
{
  "status": "success",
  "data": {
    "sessid": "118224010583f7fc26158e4edd065355"
  },
  "errors": []
}
```

## Авторизация
Включается в настройках модуля boilerplate.tools  

### Залогиниться
**Method:** POST  
**Url:** /api/auth/login   
**Csrf:** true  
**Request:**
```json
{
  "login": "login",
  "password": "password"
}
```
Response:
```json
{
  "status": "success",
  "data": {
    "message": "User authorized",
    "user": {
      "id": "1",
      "login": "admin",
      "email": "n.garashchenko@uplab.ru",
      "firstName": "Наталия",
      "lastName": "Гаращенко",
      "token": ""
    },
    "sessid": "ab404755d11be70f20cc3cf14f634b39"
  },
  "errors": []
}
```

### Разлогиниться  
**Method:** POST  
**Url:** /api/auth/logout  
**Csrf:** false  
**Request:**
```json
{
}
```
**Response:**
```json
{
  "status": "success",
  "data": {
    "sessid": "118224010583f7fc26158e4edd065355"
  },
  "errors": []
}
```

### Проверить авторизацию
**Method:** POST  
**Url:** /api/auth/check  
**Csrf:** false   
**Request:**
```json
{
}
```
**Response:**
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": "1",
      "login": "admin",
      "email": "n.garashchenko@uplab.ru",
      "firstName": "Наталия",
      "lastName": "Гаращенко",
      "token": ""
    },
    "sessid": "118224010583f7fc26158e4edd065355"
  },
  "errors": []
}
```

### Зарегистрироваться
Регулируется в настройках Главного модуля (все работает, кроме шифрования пароля).  
**Method:** POST  
**Url:** /api/auth/register   
**Csrf:** true   
**Request:**
```json
{
  "login": "login",
  "password": "password",
  "confirm_password": "password",
  "email": "email@email.com",
  "phone_number": "+79111231212"
}
```
**Response:**
```json
{
  "status": "success",
  "data": {
    "user_id": 12,
    "user": {
      "id": "12",
      "login": "testlogin",
      "email": "mail@mailrest.ru",
      "firstName": "",
      "lastName": "",
      "token": ""
    },
    "sessid": "38d0c789a18ca7a1bec1fc1b2eb59ed5"
  },
  "errors": []
}
```