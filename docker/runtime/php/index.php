<?php

include __DIR__.'/vendor/autoload.php';

$app = new \Kubeless\Controller(
    getenv('FUNC_TIMEOUT') ? getenv('FUNC_TIMEOUT') : 30,
    getenv('FUNC_HANDLER'),
    getenv('MOD_NAME'),
    getenv('MOD_ROOT_PATH') ? getenv('MOD_ROOT_PATH') : '/kubeless/',
    getenv('FUNC_RUNTIME'),
    getenv('FUNC_MEMORY_LIMIT')
);
$app->run();
