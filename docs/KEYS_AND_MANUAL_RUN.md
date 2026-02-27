# Ключи доступа и ручной запуск

Этот чеклист нужен, чтобы выпустить ключи OpenAI, Yandex и DeepSeek, а затем прогнать один и тот же запрос через всех провайдеров.

## 1) OpenAI: выпуск API-ключа

1. Войдите в OpenAI Platform.
2. Откройте страницу управления API-ключами.
3. Создайте новый secret key.
4. Сохраните ключ в локальный `.env` как `OPENAI_API_KEY`.

Официальные ссылки:
- https://help.openai.com/en/articles/4936850-where-do-i-find-my-openai-api-key
- https://platform.openai.com/settings/organization/api-keys

## 2) Yandex: выпуск API-ключа (с учетом реальных кейсов)

### Важный контекст

Если вы находитесь на `center.yandex.cloud` (Cloud Center), там может не быть привычного меню IAM для сервисных аккаунтов.

Для ключей YandexGPT переходите в **Cloud Console** (`console.yandex.cloud`):
1. Откройте Cloud Center.
2. Нажмите кнопку **Cloud Console**.
3. Выберите нужный `cloud/folder`.

### Пошагово

1. Откройте **Все сервисы** -> **Identity and Access Management (IAM)**.
2. Перейдите в **Сервисные аккаунты**.
3. Создайте сервисный аккаунт (если его еще нет).
4. Выдайте сервисному аккаунту роль минимум `ai.languageModels.user` на нужный folder.
5. Откройте сервисный аккаунт -> **Создать новый ключ**.
6. Выберите именно **Создать API-ключ**.
7. Сохраните `secret` (показывается один раз).

### Какой тип ключа нужен

В меню Яндекса обычно 3 варианта:
- `Создать статический ключ доступа` -> не нужен для этого проекта.
- `Создать API-ключ` -> нужен.
- `Создать авторизованный ключ` -> не нужен для этого проекта.

Для текущей библиотеки нужен именно `API-ключ`, потому что провайдер отправляет заголовок:
`Authorization: Api-Key <YANDEX_API_KEY>`.

### Что записать в `.env`

- `YANDEX_API_KEY` -> секрет API-ключа сервисного аккаунта.
- `YANDEX_FOLDER_ID` -> ID папки (folder), где вы используете модель.

### Если IAM не видно

Скорее всего, не хватает прав в папке/облаке. Нужны права на работу с сервисными аккаунтами (например, роль уровня IAM admin для нужного scope).

Официальные ссылки:
- https://yandex.cloud/en/docs/ai-studio/operations/get-api-key
- https://yandex.cloud/en/docs/iam/operations/authentication/manage-api-keys
- https://yandex.cloud/en/docs/iam/operations/sa/create

## 3) DeepSeek: выпуск API-ключа

1. Войдите в DeepSeek Platform.
2. Откройте страницу API keys.
3. Создайте ключ.
4. Сохраните его в `.env` как `DEEPSEEK_API_KEY`.

Официальные ссылки:
- https://api-docs.deepseek.com/
- https://platform.deepseek.com/api_keys

## 4) Локальная конфигурация

Скопируйте шаблон и заполните значения:

```bash
cp examples/.env.example .env
```

В этом проекте `examples/04_manual_compare.php` сам загружает `.env` через `phpdotenv`, поэтому `source .env` не нужен.

## 5) Ручной запуск одинакового запроса

```bash
php examples/04_manual_compare.php "Объясни RAG простыми словами в 5 пунктах"
```

Опционально можно передать свой путь для отчета:

```bash
php examples/04_manual_compare.php "Объясни RAG..." "/tmp/compare.json"
```

Скрипт печатает результат по каждому провайдеру и сохраняет JSON-отчет (статус, модель, latency, tokens, текст ответа).

## 6) Типовые проблемы и быстрая диагностика

### Биллинг и баланс (обязательно для всех провайдеров)

Для OpenAI, Yandex и DeepSeek должен быть активный биллинг и положительный баланс (или доступный кредит/лимит).

Если баланс нулевой или превышены лимиты, запросы могут падать даже при корректных ключах:
- `401/403` (в зависимости от политики провайдера);
- `429` (`rate limit`/`quota exceeded`);
- ошибки про отсутствие квоты или необходимости проверить billing.

### `Skipped: missing API credentials in environment`

Причины:
- не заполнен `.env`;
- неверные имена переменных;
- скрипт запущен не из этого репозитория.

Проверьте, что в `.env` заданы:
- `OPENAI_API_KEY`
- `YANDEX_API_KEY`
- `YANDEX_FOLDER_ID`
- `DEEPSEEK_API_KEY`

### OpenAI: `RateLimitException` / `You exceeded your current quota`

Это обычно не баг кода, а квота/биллинг в OpenAI.

### Yandex: ошибка авторизации или доступа

Проверьте:
- используется именно `API-ключ` (не статический и не авторизованный);
- сервисному аккаунту выдана роль на нужный folder;
- `YANDEX_FOLDER_ID` соответствует той папке, где выдали права.
