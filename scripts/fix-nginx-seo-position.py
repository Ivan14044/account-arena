#!/usr/bin/env python3
import re

config_file = '/etc/nginx/sites-available/account-arena'

with open(config_file, 'r') as f:
    content = f.read()

# Удаляем все SEO блоки
content = re.sub(r'\n\s*# SEO.*?\n\s*location ~ \^/seo/.*?\n\s*\}\n', '\n', content, flags=re.DOTALL)

# Находим место после supplier routes и добавляем SEO блок
pattern = r'(location /supplier \{[^}]*\})'
seo_block = '''    # SEO страницы (SSR через Laravel)
    location ~ ^/seo/(categories|articles|products)/ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/account-arena/backend/public/index.php;
        include fastcgi_params;
        fastcgi_param REQUEST_URI $request_uri;
    }'''

if 'location ~ ^/seo/(categories|articles|products)/' not in content:
    content = re.sub(pattern, r'\1\n\n' + seo_block, content, flags=re.DOTALL)

with open(config_file, 'w') as f:
    f.write(content)

print('SEO блок добавлен в правильное место')
