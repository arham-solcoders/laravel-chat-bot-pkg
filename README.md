# DB Chatbot

Rule-based database chatbot for Laravel. It answers shop questions from registered tools and can render PDF and Word reports. There is no LLM and no external AI API.

An LLM driver can be added later by implementing `DbChatbot\Chat\Drivers\ChatDriver`. The tools stay the same.

## Requirements

- PHP 8.3 or newer
- Laravel 13
- A Blade layout component (the default is `app-layout`)

## Install

```bash
composer require db-chatbot/laravel
```

Laravel discovers `DbChatbot\DbChatbotServiceProvider`.

1. On the host `User` model, implement `DbChatbot\Contracts\ChatUser`, use `DbChatbot\Concerns\InteractsWithDbChatbot`, and cast `role` to `DbChatbot\Enums\Role`.
2. Run `php artisan migrate`. The package adds `role` on `users` and the shop, chat, audit, and report tables.
3. Optional: `php artisan vendor:publish --tag=db-chatbot-config` and change `layout` when the host layout component is not `app-layout`.

Authenticated routes:

- `GET/POST/DELETE /chat`
- `GET /admin/audit` for admins
- signed `GET /reports/{report}`

Report files use the `reports` disk (`storage/app/reports`). The package registers that disk when the host has not defined it. `reports:prune` runs daily and deletes files older than 24 hours.

## Authorization

Every tool receives the signed-in user through `DbChatbot\Contracts\ChatUser` and checks a policy or gate itself. Customers can see only their own orders. A missing order and a forbidden order both return `Order not found.`

## Adding a tool

1. Add a class under `src/Chat/Tools` that implements `DbChatbot\Chat\Tools\Tool`.
2. Register it in `config/chatbot.php`. The first matching tool wins.
3. Enforce access inside `handle()`. Read data with Eloquent and validate every argument.
4. Return a `ToolResult`. Do not return password hashes, tokens, `remember_token`, or card data.
