# 🤖 Telegram Bot Integration

Этот проект включает интеграцию с Telegram Bot API для создания AI-ассистента, который отвечает на вопросы на основе базы данных.

## 📋 Требования

- Laravel 12
- PHP 8.2+
- Telegram Bot Token
- OpenAI API Key

## ⚙️ Настройка

### 1. Установка зависимостей

```bash
composer install
```

### 2. Настройка переменных окружения

Добавьте в файл `.env`:

```env
# Telegram Bot
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here

# OpenAI
OPEN_AI_KEY=your_openai_api_key_here
```

### 3. Получение Telegram Bot Token

1. Найдите @BotFather в Telegram
2. Отправьте команду `/newbot`
3. Следуйте инструкциям для создания бота
4. Скопируйте полученный токен в `.env` файл

### 4. Настройка Webhook

Для продакшена выполните:

```bash
php artisan telegram:bot set-webhook --url=https://yourdomain.com/telegram/webhook
```

Для локальной разработки используйте ngrok:

```bash
# Установите ngrok
ngrok http 8000

# Установите webhook с URL от ngrok
php artisan telegram:bot set-webhook --url=https://your-ngrok-url.ngrok.io/telegram/webhook
```

## 🚀 Использование

### Доступные команды

```bash
# Получить информацию о боте
php artisan telegram:bot info

# Установить webhook
php artisan telegram:bot set-webhook --url=https://yourdomain.com/telegram/webhook

# Удалить webhook
php artisan telegram:bot delete-webhook

# Получить информацию о webhook
php artisan telegram:bot webhook-info

# Очистить устаревшие сессии
php artisan sessions:clean
```

### Команды бота

- `/start` - Начать разговор с ботом
- `/help` - Показать справку
- `/ask` - Задать вопрос коту Харитону (интерактивный режим)
- `/status` - Проверить статус бота
- Любой текст - Получить AI-ответ на основе базы данных

## 📁 Структура файлов

```
app/
├── Console/Commands/
│   ├── TelegramBotCommand.php          # Команды для управления ботом
│   └── CleanUserSessionsCommand.php    # Очистка устаревших сессий
├── Http/Controllers/
│   └── TelegramWebhookController.php   # Обработчик webhook'ов
├── Models/
│   └── UserSession.php                 # Модель пользовательских сессий
└── Services/
    ├── OpenAIService.php               # Сервис для работы с OpenAI
    ├── TelegramBotService.php          # Сервис для работы с Telegram API
    └── UserSessionService.php          # Сервис управления сессиями

routes/
└── web.php                             # Маршруты для webhook'ов

resources/views/
└── telegram.blade.php                  # Страница управления ботом
```

## 🔧 Функциональность

### TelegramBotService

Основной сервис для работы с Telegram Bot API:

- `sendMessage()` - Отправка сообщений
- `getMe()` - Получение информации о боте
- `setWebhook()` - Установка webhook
- `deleteWebhook()` - Удаление webhook
- `getWebhookInfo()` - Получение информации о webhook
- `sendKeyboardMessage()` - Отправка сообщений с клавиатурой
- `sendInlineKeyboardMessage()` - Отправка сообщений с inline клавиатурой

### TelegramWebhookController

Обработчик входящих webhook'ов от Telegram:

- Обработка команд (`/start`, `/help`, `/ask`, `/status`)
- Интерактивный режим для команды `/ask`
- Интеграция с OpenAI для ответов на вопросы
- Логирование всех взаимодействий
- Обработка callback query (inline кнопки)

### OpenAIService

Сервис для работы с OpenAI API:

- `ask()` - Задать вопрос и получить ответ
- `generatePromptWithSimilarQuestions()` - Генерация промпта с похожими вопросами
- `findSimilarQuestions()` - Поиск похожих вопросов в базе данных

### UserSessionService

Сервис для управления пользовательскими сессиями:

- `getSession()` - Получить или создать сессию пользователя
- `setState()` - Установить состояние сессии
- `clearState()` - Очистить состояние сессии
- `isInState()` - Проверить, находится ли пользователь в определенном состоянии
- `cleanExpiredSessions()` - Очистить устаревшие сессии

## 📊 Логирование

Все взаимодействия с ботом логируются в `storage/logs/laravel.log`:

- Входящие webhook'ы
- Отправленные сообщения
- Ошибки API
- Информация о пользователях

## 🛡️ Безопасность

### CSRF Protection

Webhook маршрут исключен из CSRF защиты в `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'telegram/webhook',
]);
```

### Валидация токенов

- Проверка наличия Telegram Bot Token
- Проверка наличия OpenAI API Key
- Логирование ошибок аутентификации

## 🚀 Развертывание

### Локальная разработка

1. Запустите сервер разработки:
```bash
php artisan serve
```

2. Используйте ngrok для туннелирования:
```bash
ngrok http 8000
```

3. Установите webhook:
```bash
php artisan telegram:bot set-webhook --url=https://your-ngrok-url.ngrok.io/telegram/webhook
```

### Продакшен

1. Убедитесь, что у вас есть SSL сертификат
2. Установите webhook с HTTPS URL:
```bash
php artisan telegram:bot set-webhook --url=https://yourdomain.com/telegram/webhook
```

3. Настройте мониторинг логов:
```bash
tail -f storage/logs/laravel.log
```

## 🔍 Отладка

### Проверка статуса бота

```bash
php artisan telegram:bot info
```

### Проверка webhook

```bash
php artisan telegram:bot webhook-info
```

### Тестирование webhook

```bash
curl -X POST http://localhost:8000/telegram/webhook \
  -H "Content-Type: application/json" \
  -d '{"update_id":123,"message":{"message_id":1,"from":{"id":123456,"first_name":"Test"},"chat":{"id":123456},"text":"/start"}}'
```

### Просмотр логов

```bash
tail -f storage/logs/laravel.log
```

## 📱 Страница управления

Доступна по адресу: `http://yourdomain.com/telegram`

Содержит:
- Информацию о конфигурации бота
- Доступные команды
- Инструкции по настройке
- Статус webhook

## 🤝 Поддержка

При возникновении проблем:

1. Проверьте логи: `storage/logs/laravel.log`
2. Убедитесь, что все переменные окружения настроены
3. Проверьте статус webhook: `php artisan telegram:bot webhook-info`
4. Убедитесь, что сервер доступен по HTTPS (для продакшена) 