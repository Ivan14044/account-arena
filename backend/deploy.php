<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:Ivan14044/ai-bot.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('subcloudy.com')
    ->set('remote_user', 'deployer')
    ->set('hostname', 'subcloudy.com')
    ->set('identity_file', '~/.ssh/id_ed25519')
    ->set('deploy_path', '/var/www/ai-bot');

// Hooks

after('deploy:failed', 'deploy:unlock');
