#!/bin/bash

# Финальный скрипт настройки nginx для SEO (FIX REGEX)

NGINX_CONFIG="/etc/nginx/sites-available/account-arena"
    BACKUP_FILE="/etc/nginx/sites-available/account-arena.backup.final.v3"

cp "$NGINX_CONFIG" "$BACKUP_FILE"

# Создаем чистую конфигурацию
cat > /tmp/nginx_new.conf << 'EOF'
server {
    server_name account-arena.com www.account-arena.com;
    client_max_body_size 64M;
    root /var/www/account-arena/frontend/dist;
    index index.html;
    
    access_log /var/log/nginx/account-arena-access.log;
    error_log /var/log/nginx/account-arena-error.log;

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml+rss;

    # Static Files
    location ^~ /vendor/ { alias /var/www/account-arena/backend/public/vendor/; expires 1y; access_log off; }
    location ^~ /assets/admin/ { alias /var/www/account-arena/backend/public/assets/admin/; expires 1y; access_log off; }
    location ^~ /storage/ { alias /var/www/account-arena/backend/storage/app/public/; expires 1y; access_log off; }

    # Robots & Sitemap
    location = /robots.txt {
        root /var/www/account-arena/backend/public;
        allow all;
        log_not_found off;
        access_log off;
    }

    location = /sitemap.xml {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # Laravel Backend
    location ~ ^/(api|auth|admin|supplier) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # SSR SEO Routes
    location ^~ /seo/ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # SPA Routes with Meta Injection (FIXED REGEX)
    location ~ ^/(account|articles|categories|become-supplier|conditions|payment-refund|contacts) {
        if ($uri ~ \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot|webp|json|xml|map)$) { return 404; }
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    # Home Page with Meta Injection
    location = / {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }

    location / {
        try_files $uri $uri/ @fallback;
    }

    location @fallback {
        if ($uri ~ \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot|webp|json|xml)$) { return 404; }
        try_files /index.html =404;
    }

    # SSL (Let's Encrypt)
    listen [::]:443 ssl ipv6only=on;
    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/account-arena.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/account-arena.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

server {
    if ($host = www.account-arena.com) { return 301 https://$host$request_uri; }
    if ($host = account-arena.com) { return 301 https://$host$request_uri; }
    listen 80;
    listen [::]:80;
    server_name account-arena.com www.account-arena.com;
    return 404;
}
EOF

mv /tmp/nginx_new.conf "$NGINX_CONFIG"

if nginx -t; then
    systemctl reload nginx
    echo "Nginx updated successfully"
else
    cp "$BACKUP_FILE" "$NGINX_CONFIG"
    echo "Error in nginx config, rolled back"
    exit 1
fi
