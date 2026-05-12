# Перенос проекта на новый сервер

Документ описывает полный процесс миграции CRM-системы Again с одного сервера на другой.
Подходит для повторного использования при следующих переездах.

- **Source (старый)**: `193.233.18.235` — Novosibirsk, AS207713 GLOBAL INTERNET SOLUTIONS LLC, домены `againdev.ru` + `againdev2.ru`
- **Target (новый)**: `186.246.14.59` — Novosibirsk, AS57494 Adman LLC, домены `sub.againdev.ru` + `sub.againdev2.ru`
- **Стек**: PHP 8.3 + Laravel + Vue Admin (vue-cli) + Nuxt 3 + MySQL 8 + Reverb (WebSocket) + WhatsApp-сервис на puppeteer + PM2

---

## 0. Архитектура проекта (что переносим)

| Компонент | Путь на сервере | Назначение | Домен/маршрут |
|---|---|---|---|
| Laravel API | `/var/www/html/laravel` | Backend, REST API, очереди, scheduler | `https://sub.againdev.ru/api/*` |
| Vue Admin | `/var/www/html/vue-admin` (SPA → `dist/`) | Админ-панель | `https://sub.againdev.ru/admin/` |
| Nuxt Shop | `/var/www/html/nuxt-shop` (SSR) | Витрина / клиентский фронт | `https://sub.againdev2.ru/` |
| WhatsApp Service | `/var/www/html/laravel/whatsapp-service` (Node + whatsapp-web.js + puppeteer + chromium) | Отправка/приём сообщений WA | `https://sub.againdev.ru/whatsapp/*` (порт 3002) |
| Reverb | внутри Laravel | WebSocket-сервер | `wss://sub.againdev.ru/app` (порт 8001) |
| phpMyAdmin | `/var/www/html/phpmyadmin` | Web-доступ к БД | `https://sub.againdev.ru/phpmyadmin/` |
| MySQL `laravel` | systemd service | Основная БД | localhost:3306 |

Управление процессами — **PM2** (5 приложений: `laravel-queue`, `laravel-reverb`, `laravel-scheduler`, `whatsapp-service`, `nuxt-shop`).

---

## 1. Подготовка target-сервера (`186.246.14.59`)

### 1.1 Установить пакеты

```bash
ssh root@186.246.14.59

apt update && apt install -y \
  nginx \
  php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-redis php8.3-imap \
  mysql-server \
  composer \
  certbot python3-certbot-nginx \
  snapd

# Node.js 20 (NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# PM2
npm install -g pm2@latest

# Chromium для whatsapp-web.js (через snap)
systemctl enable --now snapd.socket
snap install chromium
apt install -y chromium-browser   # враппер /usr/bin/chromium-browser → /snap/bin/chromium
```

Проверить версии:
```bash
nginx -v && php -v | head -1 && mysql --version && node -v && pm2 -v && composer --version
```

### 1.2 Настроить MySQL

```bash
systemctl enable --now mysql

# Установить пароль root (точно такой же, как на source — упростит миграцию .env)
mysql -uroot -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'again123'; FLUSH PRIVILEGES;"

# Создать БД с такой же кодировкой
mysql -uroot -pagain123 -e "CREATE DATABASE IF NOT EXISTS laravel DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 1.3 Запустить сервисы

```bash
systemctl enable --now nginx php8.3-fpm mysql snapd
```

---

## 2. Перенос кода (rsync с source)

С source-сервера выполняем `rsync`. Исключаем тяжёлые/генерируемые директории — пересоберём на target.

> **SSH-ключ.** Сначала сгенерировать ключ на source (если нет) и положить публичную часть в `/root/.ssh/authorized_keys` на target.
> ```bash
> ssh-keygen -t rsa -f ~/.ssh/id_rsa_migrate -N ''
> ssh-copy-id -i ~/.ssh/id_rsa_migrate.pub root@186.246.14.59
> ```

### 2.1 Laravel

```bash
rsync -az --delete -e "ssh -i ~/.ssh/id_rsa_migrate" \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.git' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/data/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='bootstrap/cache/*.php' \
  /var/www/html/laravel/ root@186.246.14.59:/var/www/html/laravel/
```

### 2.2 Vue Admin

```bash
rsync -az --delete -e "ssh -i ~/.ssh/id_rsa_migrate" \
  --exclude='node_modules' --exclude='.git' \
  /var/www/html/vue-admin/ root@186.246.14.59:/var/www/html/vue-admin/
```

### 2.3 Nuxt Shop

```bash
rsync -az --delete -e "ssh -i ~/.ssh/id_rsa_migrate" \
  --exclude='node_modules' --exclude='.output' --exclude='.nuxt' --exclude='.git' \
  /var/www/html/nuxt-shop/ root@186.246.14.59:/var/www/html/nuxt-shop/
```

### 2.4 phpMyAdmin (если используется)

```bash
rsync -az -e "ssh -i ~/.ssh/id_rsa_migrate" /var/www/html/phpmyadmin/ root@186.246.14.59:/var/www/html/phpmyadmin/
```

---

## 3. Перенос базы данных

### На source — снять dump

```bash
mysqldump -uroot -pagain123 --single-transaction --quick --lock-tables=false \
  --default-character-set=utf8mb4 laravel | gzip > /tmp/laravel.sql.gz
ls -lh /tmp/laravel.sql.gz
```

### Передать и импортировать

```bash
scp -i ~/.ssh/id_rsa_migrate /tmp/laravel.sql.gz root@186.246.14.59:/tmp/

ssh -i ~/.ssh/id_rsa_migrate root@186.246.14.59 \
  "zcat /tmp/laravel.sql.gz | mysql -uroot -pagain123 laravel"

# Проверка
ssh -i ~/.ssh/id_rsa_migrate root@186.246.14.59 \
  "mysql -uroot -pagain123 -e 'USE laravel; SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"laravel\";'"
```

---

## 4. Адаптация ENV-файлов под новые домены

> На source домены: `againdev.ru` / `againdev2.ru` — заменяем на `sub.againdev.ru` / `sub.againdev2.ru`.
> Важно: используем **negative lookbehind** в Perl, чтобы не получить «sub.sub.». Простой `sed` `s/againdev.ru/sub.againdev.ru/` сломается, если строка уже содержит `https://sub.againdev.ru`.

На target:

```bash
ssh root@186.246.14.59 'bash -s' <<'EOF'
fix() {
  perl -pe 's/(?<!sub\.)\bagaindev2\.ru\b/sub.againdev2.ru/g;
            s/(?<!sub\.)\bagaindev\.ru\b/sub.againdev.ru/g' "$1.orig" > "$1"
}

# Сохранить бэкап исходных .env (если уже есть)
for f in /var/www/html/laravel/.env \
         /var/www/html/nuxt-shop/.env \
         /var/www/html/laravel/whatsapp-service/.env \
         /var/www/html/vue-admin/.env \
         /var/www/html/vue-admin/.env.local; do
  [ -f "$f" ] && cp "$f" "$f.orig"
  [ -f "$f.orig" ] && fix "$f"
done
EOF
```

Что должно остаться в важных .env (`/var/www/html/laravel/.env`):

```
APP_URL=https://sub.againdev.ru
FRONTEND_URL=https://sub.againdev2.ru/
DB_HOST=127.0.0.1
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=again123
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
BROADCAST_DRIVER=reverb
REVERB_HOST="127.0.0.1"
REVERB_PORT=8001
REVERB_SCHEME=http
MAIL_HOST=smtp.yandex.ru
MAIL_PORT=465
MAIL_USERNAME=help@again8.ru
WHATSAPP_SERVICE_URL=https://sub.againdev.ru/whatsapp
MAX_WEBHOOK_URL=https://sub.againdev.ru
```

---

## 5. Установка зависимостей и билды

### 5.1 Laravel (composer)

```bash
ssh root@186.246.14.59 'cd /var/www/html/laravel && COMPOSER_ALLOW_SUPERUSER=1 \
  composer install --no-interaction --no-dev --optimize-autoloader'

# Storage link + чистка кешей
cd /var/www/html/laravel
php artisan storage:link   # если уже есть символлинк — это ок
php artisan config:clear && php artisan cache:clear \
  && php artisan view:clear && php artisan route:clear

# Права
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 5.2 WhatsApp Service

```bash
cd /var/www/html/laravel/whatsapp-service
npm install --omit=dev
```

### 5.3 Vue Admin — пересобрать с новыми URL

> Билд содержит хардкод URL из `.env`. На source dist имеет `https://againdev.ru`, нужно перебилдить.

```bash
cd /var/www/html/vue-admin
rm -rf node_modules package-lock.json
npm install --legacy-peer-deps   # есть peerDeps конфликт chart.js / vue-chart-3
npm run build                    # vue-cli-service build, ~80s
```

Убедиться, что в `dist/` нет старых URL:
```bash
grep -roh 'https://[a-z0-9.-]*againdev[a-z0-9.-]*' dist | sort -u
# должно быть только https://sub.againdev.ru
```

> `vue.config.js` уже содержит `publicPath: '/admin/'` — это критично для подключения assets через `/admin/`.

### 5.4 Nuxt Shop — пересобрать

```bash
cd /var/www/html/nuxt-shop
npm install --legacy-peer-deps
npm run build   # выводит в .output/
```

---

## 6. Nginx + SSL

### 6.1 Конфиги виртуал-хостов

`/etc/nginx/sites-available/sub.againdev.ru`:

```nginx
server {
    listen 80;
    server_name sub.againdev.ru;
    root /var/www/html/laravel/public;
    index index.php index.html;
    client_max_body_size 100M;

    # Reverb WebSocket
    location /app {
        proxy_pass http://127.0.0.1:8001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
    }

    location /whatsapp/ {
        proxy_pass http://localhost:3002/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /admin {
        alias /var/www/html/vue-admin/dist;
        location ~ \.(js|css|svg|png|jpg|jpeg|gif|ico|woff|woff2|ttf|eot)$ {
            expires 30d;
            add_header Cache-Control "public, immutable";
        }
        try_files $uri $uri/ /admin/index.html;
    }

    location /phpmyadmin {
        root /var/www/html;
        index index.php;
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_read_timeout 1800;
        }
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 1800;
    }

    location ~ /\.ht { deny all; }
}
```

`/etc/nginx/sites-available/sub.againdev2.ru`:

```nginx
server {
    listen 80;
    server_name sub.againdev2.ru;
    client_max_body_size 100M;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header Authorization $http_authorization;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Активировать:
```bash
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/sub.againdev.ru /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/sub.againdev2.ru /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### 6.2 Let's Encrypt сертификаты

> DNS на оба домена должен быть направлен на target IP **до** запуска certbot.

```bash
certbot --nginx --non-interactive --agree-tos --redirect \
  --email admin@againdev.ru \
  -d sub.againdev.ru -d sub.againdev2.ru
```

Certbot автоматически дополнит nginx-конфиги блоками `listen 443 ssl` и редиректами `80 → 443`.

---

## 7. PM2 — все воркеры

`/var/www/html/laravel/laravel-ecosystem.config.js`:

```js
module.exports = {
  apps: [
    {
      name: 'laravel-queue',
      script: 'artisan',
      interpreter: 'php',
      args: 'queue:work database --sleep=3 --tries=3 --max-time=3600',
      cwd: '/var/www/html/laravel',
      max_memory_restart: '500M',
      error_file: './storage/logs/queue-error.log',
      out_file: './storage/logs/queue-out.log',
    },
    {
      name: 'laravel-reverb',
      script: 'bash',
      args: '-c "php artisan reverb:start"',
      cwd: '/var/www/html/laravel',
      error_file: './storage/logs/reverb-error.log',
      out_file: './storage/logs/reverb-out.log',
    },
    {
      name: 'laravel-scheduler',
      script: 'scheduler.sh',
      interpreter: 'bash',
      cwd: '/var/www/html/laravel',
    },
    {
      name: 'whatsapp-service',
      script: 'index.js',
      cwd: '/var/www/html/laravel/whatsapp-service',
    },
    {
      name: 'nuxt-shop',
      script: 'bash',
      args: '-c "node .output/server/index.mjs"',
      cwd: '/var/www/html/nuxt-shop',
      env: { PORT: 3000, HOST: '127.0.0.1', NODE_ENV: 'production' },
    },
  ],
};
```

`/var/www/html/laravel/scheduler.sh`:
```bash
#!/bin/bash
cd /var/www/html/laravel
while true; do
  php artisan schedule:run >> /dev/null 2>&1
  sleep 60
done
```
```bash
chmod +x /var/www/html/laravel/scheduler.sh
```

Запуск + автостарт:
```bash
cd /var/www/html/laravel
pm2 delete all 2>/dev/null || true
pm2 start laravel-ecosystem.config.js
pm2 save
pm2 startup systemd -u root --hp /root   # systemd-юнит pm2-root
```

---

## 8. CORS (важно при смене доменов)

`/var/www/html/laravel/config/cors.php` — в `allowed_origins` обязательно добавить новые домены (иначе Nuxt-чат и любые cross-origin запросы будут падать с `Failed to fetch`):

```php
'allowed_origins' => [
    'https://sub.againdev.ru',
    'https://sub.againdev2.ru',
    // ... остальные (оставить старые на случай переходного периода)
    'https://againdev.ru',
    'https://againdev2.ru',
    'http://localhost:3000',
    // ...
],
'supports_credentials' => true,
```

После правки: `php artisan config:clear`.

---

## 9. Перенастройка внешних webhook'ов мессенджеров

Внешние сервисы хранят URL у себя — после смены домена их **обязательно** надо перенастроить.

### 9.1 MAX bot (мессенджер MAX)

Подписки хранятся у MAX. Через MaxService:

```bash
cd /var/www/html/laravel
# Зарегистрировать новый webhook
php artisan tinker --execute='
$s = app(\App\Services\Max\MaxService::class);
print_r($s->registerWebhookIfNeeded());
print_r($s->getWebhookSubscriptions());
'
# Отписать старые URL (повторить для каждого старого)
php artisan tinker --execute='
app(\App\Services\Max\MaxService::class)
  ->unregisterWebhook("https://againdev.ru/api/public/max/webhook");
'
```

> Bot token берётся из таблицы `max_settings` (поле `bot_token`, `is_active=1`), а **не** из `.env`. `MAX_BOT_TOKEN` в `.env` фактически не используется в MaxService.

### 9.2 Telegram bot

> ⚠️ Если target-сервер в РФ на хостере с фильтрацией Telegram (как Adman LLC AS57494) — Telegram физически не сможет доставлять webhook'и (Connection timed out). См. раздел **10. Cloudflare proxy** ниже.

```bash
TOKEN="<bot_token из таблицы telegraph_bots>"
NEW_URL="https://sub.againdev.ru/telegraph/${TOKEN}/webhook"

curl -sS -X POST "https://api.telegram.org/bot${TOKEN}/setWebhook" \
  --data-urlencode "url=${NEW_URL}" \
  --data-urlencode "allowed_updates=[\"message\",\"callback_query\"]" \
  --data-urlencode "max_connections=40"

# Проверка
curl -sS "https://api.telegram.org/bot${TOKEN}/getWebhookInfo" | python3 -m json.tool
```

`setWebhook` можно вызывать с любой машины (Telegram не требует, чтобы запрос приходил с того же IP, что и webhook).

### 9.3 VK Callback API

VK хранит callback-серверы у себя. Алгоритм:

```bash
VK_TOKEN="<access_token из vk_settings>"
GROUP_ID="<community_id из vk_settings>"

# 1. Посмотреть текущие
curl -sS "https://api.vk.com/method/groups.getCallbackServers?group_id=${GROUP_ID}&access_token=${VK_TOKEN}&v=5.199" | python3 -m json.tool

# 2. Получить актуальный confirmation code (он может отличаться от того, что в БД!)
curl -sS "https://api.vk.com/method/groups.getCallbackConfirmationCode?group_id=${GROUP_ID}&access_token=${VK_TOKEN}&v=5.199"

# 3. Если confirmation_token в БД устарел — обновить:
mysql -uroot -pagain123 -e "UPDATE laravel.vk_settings SET confirmation_token='<new_code>', updated_at=NOW();"

# 4. Удалить старый сервер и добавить новый (заставляет VK заново сделать confirmation)
curl -sS "https://api.vk.com/method/groups.deleteCallbackServer" \
  --data-urlencode "group_id=${GROUP_ID}" \
  --data-urlencode "server_id=<old_id>" \
  --data-urlencode "access_token=${VK_TOKEN}" --data-urlencode "v=5.199"

ADD=$(curl -sS "https://api.vk.com/method/groups.addCallbackServer" \
  --data-urlencode "group_id=${GROUP_ID}" \
  --data-urlencode "url=https://sub.againdev.ru/api/public/vk/webhook" \
  --data-urlencode "title=Сервер 1" \
  --data-urlencode "access_token=${VK_TOKEN}" --data-urlencode "v=5.199")
SID=$(echo "$ADD" | python3 -c 'import json,sys; print(json.load(sys.stdin)["response"]["server_id"])')

# 5. Подписаться на события
curl -sS "https://api.vk.com/method/groups.setCallbackSettings" \
  --data-urlencode "group_id=${GROUP_ID}" \
  --data-urlencode "server_id=${SID}" \
  --data-urlencode "api_version=5.199" \
  --data-urlencode "message_new=1" \
  --data-urlencode "message_reply=1" \
  --data-urlencode "message_allow=1" \
  --data-urlencode "message_edit=1" \
  --data-urlencode "message_typing_state=1" \
  --data-urlencode "access_token=${VK_TOKEN}" --data-urlencode "v=5.199"

# 6. Убедиться что status = "ok"
curl -sS "https://api.vk.com/method/groups.getCallbackServers?group_id=${GROUP_ID}&access_token=${VK_TOKEN}&v=5.199" | python3 -m json.tool
```

### 9.4 WhatsApp

Внешнего webhook нет — `whatsapp-service` сам поднимает Puppeteer/Chromium и держит сессию. После старта в логах `pm2 logs whatsapp-service` появится QR — отсканировать в WhatsApp Business → Связанные устройства.

---

## 10. Решение проблемы блокировки Telegram у хостера (Cloudflare proxy)

Если хостер фильтрует Telegram IPs (симптом: getWebhookInfo показывает `last_error_message: "Connection timed out"`, в nginx access.log нет ни одного POST с IP-диапазонов `149.154.0.0/16` / `91.108.0.0/16`):

1. Зарегистрироваться на https://dash.cloudflare.com → Add Site → `againdev.ru` → Free план.
2. CF просканирует DNS, подтвердить записи.
3. В **DNS → Records** напротив `sub.againdev.ru` включить **оранжевое облако** (Proxied). Остальные записи можно оставить серыми (DNS only).
4. **SSL/TLS → Overview → Full (strict)** (на origin валидный Let's Encrypt).
5. В кабинете регистратора `againdev.ru` сменить NS на 2 nameserver'а от Cloudflare. Ждать 5–60 минут.
6. Когда зона активна — `dig +short A sub.againdev.ru` должно возвращать IP Cloudflare (104.x / 172.x), а не реальный IP сервера.
7. На Laravel включить trust к CF IPs:

`app/Http/Middleware/TrustProxies.php`:
```php
protected $proxies = '*';   // или конкретно CF CIDR https://www.cloudflare.com/ips-v4
```

В nginx-конфиг `sub.againdev.ru` добавить:
```nginx
# CF real ip
set_real_ip_from 173.245.48.0/20;
set_real_ip_from 103.21.244.0/22;
# ... (все CF ranges, актуальный список: https://www.cloudflare.com/ips-v4)
real_ip_header CF-Connecting-IP;
```

8. Повторно вызвать `setWebhook` для Telegram. Pending updates доставятся за минуту-две.

---

## 11. Финальная проверка (smoke-test)

```bash
ssh root@186.246.14.59 'bash -s' <<'EOF'
# Сервисы
for s in nginx mysql php8.3-fpm; do printf "%-15s %s\n" "$s" "$(systemctl is-active $s)"; done

# PM2
pm2 ls

# HTTP endpoints
test() { printf "  %-65s -> %s\n" "$1" "$(curl -sSo /dev/null -w '%{http_code}' --max-time 5 "$1")"; }
test "https://sub.againdev.ru/admin/"
test "https://sub.againdev.ru/api/public/conversations/client?source=web_chat&external_id=t1"
test "https://sub.againdev.ru/phpmyadmin/"
test "https://sub.againdev2.ru/"   # nuxt: 401 если стоит Basic Auth

# CORS preflight
curl -sSI -X OPTIONS -H 'Origin: https://sub.againdev2.ru' \
  -H 'Access-Control-Request-Method: GET' \
  'https://sub.againdev.ru/api/public/conversations/client' | grep -i access-control

# SMTP
cd /var/www/html/laravel
php artisan tinker --execute='try{ Mail::raw("test ".date("c"), fn($m)=>$m->to("help@again8.ru")->subject("test")); echo "MAIL_OK\n"; }catch(\Throwable $e){ echo "MAIL_ERR: ".$e->getMessage()."\n"; }'

# Ports
ss -ltn | grep -E ":(8001|3000|3002|3306) "
EOF
```

Ожидаемое:
- все systemd сервисы `active`
- все 5 PM2 процессов `online`
- `/admin/` → 200, `/api/public/...` → 200, `/phpmyadmin/` → 200, nuxt → 200 (или 401 при Basic Auth)
- `Access-Control-Allow-Origin` присутствует
- `MAIL_OK`
- слушаются порты 8001, 3000, 3002, 3306

---

## 12. Чеклист «забытых» вещей

При смене домена легко забыть:

- [ ] CORS `allowed_origins` (раздел 8)
- [ ] Vue Admin **пересобрать** (URL хардкодится в bundle)
- [ ] Nuxt **пересобрать** (то же самое)
- [ ] MAX webhook через API (раздел 9.1)
- [ ] Telegram setWebhook (9.2)
- [ ] VK Callback API + confirmation_token (9.3)
- [ ] Mail SPF/DKIM записи у Яндекса — если меняется почтовый домен (в нашем случае почта на `help@again8.ru` остаётся)
- [ ] Webhook'и платёжных систем (Tinkoff/YooKassa) — у нас их в БД не было, но проверять отдельно
- [ ] Webhook'и служб доставки (CDEK и пр.) — то же
- [ ] Yandex Metrika — добавить новый домен в счётчик (фронтенд-only, не блокер)
- [ ] DNS старого домена — оставить временно (по желанию для переходного периода)
- [ ] Сертификаты certbot auto-renew (`systemctl status certbot.timer`)
- [ ] `pm2 startup systemd` для автозапуска после reboot
- [ ] Если хостер РФ — учитывать возможную блокировку Telegram (раздел 10)

---

## 13. Откат (rollback) при проблемах

Все ENV-файлы на target сохранены как `*.env.orig` — оригиналы можно восстановить. Старый сервер не трогался, DNS можно вернуть. На уровне БД на target сохранена копия dump'а в `/tmp/laravel.sql.gz` (можно перенести в `/root/backups/` для долгого хранения):

```bash
mkdir -p /root/backups
mv /tmp/laravel.sql.gz /root/backups/laravel-$(date +%Y%m%d-%H%M%S).sql.gz
```

---

**Дата составления**: 2026-05-12. Документ описывает миграцию `193.233.18.235 → 186.246.14.59`.
