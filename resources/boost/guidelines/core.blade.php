## daikazu/robotstxt

This package serves `/robots.txt` dynamically from `config/robotstxt.php`, with separate rules per environment (keyed by `APP_ENV`).

- Edit rules in `config/robotstxt.php` under `environments.{env}`. Never create or edit `public/robots.txt`; it must not exist, or the web server serves it instead of the package route.
- Any environment not in the config (or with no `paths`) outputs `User-agent: *` / `Disallow: /`. This is intentional so non-production sites stay out of search engines.
- A published config fully replaces the package defaults, so every environment you need must be defined in it.
- Content signals (`search`, `ai_input`, `ai_train`) accept `true`/`'yes'`, `false`/`'no'` or `null` (omitted). Global `content_signals` are added to every User-agent group that has no `content_signals` of its own.
- For detailed configuration, use the `robotstxt-configuration` skill.
