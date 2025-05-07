<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

// Ignorar avisos de depreciação no PHP 8.4
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Carrega o bootstrap apenas se existir
$bootstrapFile = __DIR__ . '/../config/bootstrap.php';
if (file_exists($bootstrapFile)) {
    require $bootstrapFile;
}

$config = require __DIR__ . '/../config/web.php';

$application = new yii\web\Application($config);
$application->run();
