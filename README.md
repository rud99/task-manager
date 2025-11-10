# Task-Manager-Test
Тест задание: создаем менеджер задач.
## Развертывание
### Local
```
git clone https://github.com/rud99/task-manager.git
cd task-manager
composer install
cp .env.example .env
php artisan key:generate

php artisan app:refresh
php artisan l5-swagger:generate
php artisan test
```
### Dev & Prod
Возможный вариант деплоя в тестовую и продакшн среды можно выполнить через Github Actions.
Примеры деплоя в ```.github/workflows```
## Прочее
### Команнды
```php artisan app:refresh``` - возвращает приложение к исходному состоянию.

```php artisan l5-swagger:generate``` - генерируем документацию по API.

### Оптимизация 
В перспективе для оптимизации можно использовать кеширование данных запросов в БД.

### Примечание
Документация по API: ```api/documentation```

В данном проекте правки вносились в исходные миграции, т.к. проект тестовый и не содержит никаких критических данных. На продакшн окружении такое конечно же, не допустимо.

 
