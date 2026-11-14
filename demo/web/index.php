<?php

// Demo: php -S localhost:8091 -t demo/web
defined('YII_DEBUG') or define('YII_DEBUG', true);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$root = dirname(__DIR__, 2);

new yii\web\Application([
    'id' => 'yad-demo',
    'basePath' => dirname(__DIR__),
    'vendorPath' => "$root/vendor",
    'language' => $_GET['lang'] ?? 'de-CH',
    'aliases' => [
        '@bower' => "$root/vendor/bower-asset",
        '@npm' => "$root/vendor/npm-asset",
    ],
    'components' => [
        'request' => ['cookieValidationKey' => 'yad-demo', 'enableCsrfValidation' => false],
        'formatter' => ['timeZone' => 'UTC'],
        'assetManager' => ['forceCopy' => true, 'basePath' => __DIR__ . '/assets', 'baseUrl' => '/assets'],
    ],
]);

echo Yii::$app->view->renderFile(dirname(__DIR__) . '/views/index.php');
