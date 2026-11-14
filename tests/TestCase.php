<?php

namespace sandritsch91\yii2\yad\tests;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\Application;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        Yii::$app = null;
        parent::tearDown();
    }

    protected function mockApplication(array $config = []): void
    {
        $runtime = __DIR__ . '/runtime';
        FileHelper::createDirectory("$runtime/assets");
        new Application(ArrayHelper::merge([
            'id' => 'test',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'runtimePath' => $runtime,
            'language' => 'de-CH',
            'aliases' => [
                '@npm' => dirname(__DIR__) . '/vendor/npm-asset',
                '@web' => '/',
                '@webroot' => $runtime,
            ],
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'test',
                    'scriptFile' => __FILE__,
                    'scriptUrl' => '/index.php',
                ],
                'assetManager' => [
                    'basePath' => "$runtime/assets",
                    'baseUrl' => '/assets',
                ],
                'formatter' => [
                    'dateFormat' => 'php:d.m.Y',
                    'timeFormat' => 'php:H:i',
                    'timeZone' => 'UTC',
                ],
            ],
        ], $config));
    }
}
