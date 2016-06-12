<?php

set('repository', 'git@github.com:src-run/web-app-v1.git');
set('shared_files', [
    'config/app_prod.php',
    'config/app_dev.php',
    'config/parameters.php',
    'config/dev_services.yml'
]);
set('shared_file_fixtures', [
    'config/app_prod.php' => 'config/app_prod.php',
    'config/app_dev.php' => 'config/app_dev.php',
    'config/parameters.php' => 'config/parameters.php',
    'config/dev_services.yml' => 'config/dev_services.yml',
]);
set('var_dir', '.');
set('cache_dir', 'cache');
set('shared_dirs', [
    'cache/http'
]);
set('writable_dirs', [
    'cache/http'
]);
