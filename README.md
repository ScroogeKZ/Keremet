# Хром-KZ Логистика - PHP приложение

Система управления грузоперевозками на чистом PHP.

## Установка

```bash
composer install
cd public
php -S 0.0.0.0:8000
```

## Доступ

- **Главная**: http://localhost:8000/
- **Заказы Астана**: http://localhost:8000/astana.php
- **Региональные заказы**: http://localhost:8000/regional.php
- **Админ панель**: http://localhost:8000/admin/login.php

## Данные для входа

- **Логин**: admin
- **Пароль**: admin123

## Структура

```
public/         # Веб-страницы
├── index.php   # Главная
├── astana.php  # Форма Астана
├── regional.php # Региональные заказы
└── admin/      # Админ панель

src/            # PHP классы
├── Auth.php    # Авторизация
├── TelegramService.php # Telegram
└── Models/     # Модели данных

config/         # Конфигурация
└── database.php # База данных
```