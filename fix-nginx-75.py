#!/usr/bin/env python3
import re

config_file = '/etc/nginx/sites-available/account-arena'

with open(config_file, 'r') as f:
    lines = f.readlines()

# Исправляем строку 75 (индекс 74)
if len(lines) > 74:
    lines[74] = '        fastcgi_param REQUEST_URI $request_uri;\n'
    
    # Также исправляем все остальные пустые fastcgi_param REQUEST_URI
    for i, line in enumerate(lines):
        if re.match(r'\s*fastcgi_param REQUEST_URI\s*;', line):
            lines[i] = '        fastcgi_param REQUEST_URI $request_uri;\n'

with open(config_file, 'w') as f:
    f.writelines(lines)

print('Конфигурация исправлена')
