### Deprecated
#### The library was made quite a while ago and may be out of date. Please make a fork if you want to use it.

# Bitrix24 SDK

Крайне простой класс для работы с Bitrix24. Требуется создать входящий вебхук через раздел Приложения, а после указать токен и URL при создании экземпляра класса Bitrix.

Для обхода ошибки "Too many requests" используйте функцию queryForever, которая отправляет запрос несколько раз до первого успешного или по истечению лимита.

## Composer

Поддерживается установка через Composer:

<code>composer require mikechip/bitrix24-sdk</code>

## Feedback
Используйте **Issues**, автор всегда на проводе
