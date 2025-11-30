# Шаги для исправления ошибки на сервере

## Проблема
После `composer install --no-dev` dev-пакеты удаляются, но Laravel все еще пытается загрузить их провайдеры из кеша.

## Решение

Выполните на сервере следующие команды в указанном порядке:

```bash
cd /home/d/dsc23ytp/stroy/public_html

PHP82="/usr/local/bin/php8.2"

# 1. Удалите кеш package discovery (содержит ссылки на удаленные dev-пакеты)
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php

# 2. Очистите кеш конфигурации (может содержать ссылки на удаленные провайдеры)
$PHP82 artisan config:clear

# 3. Переобнаружьте пакеты (только установленные, без dev-зависимостей)
$PHP82 artisan package:discover --ansi

# 4. Теперь можно кешировать конфигурацию
$PHP82 artisan config:cache
```

## Альтернативный вариант (если выше не работает)

Если команды выше не работают из-за того, что Laravel не может загрузиться:

```bash
cd /home/d/dsc23ytp/stroy/public_html

# Удалите кеш напрямую через файловую систему
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes.php
rm -f bootstrap/cache/events.php

# Теперь Laravel сможет загрузиться и переобнаружить пакеты
PHP82="/usr/local/bin/php8.2"
$PHP82 artisan package:discover --ansi
$PHP82 artisan config:cache
```

