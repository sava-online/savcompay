# GlassTube — PLAN

GlassTube — спокойный видеохостинг в стиле Liquid Glass. Проект реализован как
полностью рабочий MVP без тяжёлых фреймворков: PHP 8.2+ (без Composer), MySQL 8+,
чистый CSS и Vanilla JS (ES-модули).

## Философия
- Нет бесконечной ленты, агрессивных рекомендаций и тёмных паттернов.
- Чистый стеклянный интерфейс, высокая читаемость, спокойные анимации.
- Видео удобно смотреть, сохранять, искать и организовывать.

## Архитектура
```
public/index.php   -> точка входа, автозагрузчик, bootstrap, роутер
app/Core/          -> Application, Router, Database, Request, Response,
                      Session, Csrf, Validator, View, Env, Helpers
app/Controllers/   -> обработчики маршрутов (page + api + admin)
app/Models/        -> тонкие модели с prepared statements
app/Services/      -> Auth, Video, Storage, Streaming, Ai, RateLimiter
app/Middleware/    -> AuthMiddleware, AdminMiddleware
app/Views/         -> PHP-шаблоны (layouts/main.php + страницы + admin)
public/assets/     -> css (glass.css дизайн-система, app.css), js (ES-модули)
storage/           -> videos/, thumbnails/, cache/ (вне публичной отдачи)
database/          -> DATABASE.sql, seed.sql
```

## Поток запроса
1. `public/index.php` загружает `.env`, запускает автозагрузчик `App\`.
2. `Application` инициализирует Env, Database (PDO), Session, Csrf.
3. `Router` сопоставляет URI с маршрутами (`GET /watch/{id}`).
4. Контроллер получает `App\Core\Application`, вызывает сервисы/модели.
5. View рендерит шаблон через layout `main.php`. API возвращает JSON.

## Безопасность (реализовано)
- PDO prepared statements везде (нет конкатенации пользовательского ввода в SQL).
- `e()` — экранирование вывода (XSS).
- CSRF-токен в сессии для форм и заголовок `X-CSRF-Token` для fetch.
- Загрузка: проверка размера, MIME через `finfo`, белый список расширений,
  случайные имена файлов, запись в `storage/videos/{user_id}/`.
- Стриминг через `/video/{id}/stream` с проверкой прав и HTTP Range (206/416).
- Защита маршрутов: AuthMiddleware, AdminMiddleware (проверка роли).
- ИИ-ключ только на сервере; браузер ходит только через `/api/ai/*`.
- `.htaccess` запрещает прямой доступ к `storage/` и `.env`.

## ИИ (AiService)
- Абстракция `AiProvider`: mock, openrouter, groq, custom_openai_compatible.
- `mock` работает без ключа и возвращает безопасные тестовые JSON-ответы.
- Кэш в `ai_cache`, лимиты через `RateLimiter`, логи в `ai_requests`.
- При отключении `ENABLE_AI` или ошибке провайдера — мягкий fallback.
- Функции: генерация метаданных, модерация текста, суммаризация,
  предложение тегов, умный поиск, помощник на странице просмотра.

## Статусы
- Видео: processing / published / pending_review / blocked / failed.
- Комментарии: visible / hidden / pending / flagged.
- Видимость видео: public / unlisted / private.

## Что реализовано
- Регистрация/вход/выход, роли user/admin, автосоздание канала.
- Загрузка и стриминг видео (Range), миниатюры-заглушки.
- Страница просмотра, лайки, сохранение, плейлисты, история, подписки.
- Комментарии с ИИ-модерацией, жалобы.
- Категории, теги, поиск, главная (новые/популярные/категории/продолжить).
- Админка: дашборд, пользователи, видео, комментарии, жалобы, ИИ-логи, настройки.
- Liquid Glass дизайн-система (тёмная/светлая темы, blur, скругления).
- Mock-провайдер ИИ работает из коробки.

## Известные ограничения MVP
- Нет HLS/многокачественной конвертации (FFmpeg опционален, только ffprobe для длительности).
- ИИ реально не «смотрит» видео без транскрипта (честно указано в ответах).
- Нет очередей задач (обработка упрощена: видео сразу published, если не включена премодерация).
- Транскрипты/субтитры не реализованы (заглушка в суммаризации).

## Дорожная карта
См. README.md (раздел «Дорожная карта»).
