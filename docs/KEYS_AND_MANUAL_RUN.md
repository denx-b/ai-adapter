# Keys And Manual Run

This checklist is for preparing API keys and then running the same prompt against OpenAI, Yandex, and DeepSeek.

## 1) OpenAI key

1. Sign in to the OpenAI Platform.
2. Open API key management page and create a new secret key.
3. Save the key once and store it in your local `.env`.

Official references:
- https://help.openai.com/en/articles/4936850-where-do-i-find-my-openai-api-key
- https://platform.openai.com/settings/organization/api-keys

## 2) Yandex key

1. In Yandex Cloud create/select your cloud and folder.
2. Create a service account for Foundation Models API usage.
3. Grant role `ai.languageModels.user` for the folder.
4. Create an API key for that service account.
5. Save both `YANDEX_API_KEY` and target `YANDEX_FOLDER_ID`.

Official references:
- https://yandex.cloud/en/docs/foundation-models/operations/yandexgpt/create-prompt
- https://yandex.cloud/en/docs/iam/operations/authentication/manage-api-keys

## 3) DeepSeek key

1. Sign in to DeepSeek Platform.
2. Open API keys page and create a key.
3. Save the key to `.env`.

Official references:
- https://api-docs.deepseek.com/
- https://platform.deepseek.com/api_keys

## 4) Local environment

Copy example env and fill keys:

```bash
cp examples/.env.example .env
```

Load env variables into shell:

```bash
set -a; source .env; set +a
```

## 5) Compare same prompt on all providers

```bash
php examples/04_manual_compare.php "Объясни RAG простыми словами в 5 пунктах"
```

Optional custom report file:

```bash
php examples/04_manual_compare.php "Объясни RAG..." "/tmp/compare.json"
```

The script prints console output and saves a JSON report with status, model, latency, token usage, and response text for each provider.
