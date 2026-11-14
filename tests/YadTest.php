<?php

namespace sandritsch91\yii2\yad\tests;

use PHPUnit\Framework\Attributes\DataProvider;
use sandritsch91\yii2\yad\Yad;
use sandritsch91\yii2\yad\YadAsset;
use sandritsch91\yii2\yad\YadBootstrap5Asset;
use Yii;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\web\JsExpression;

class YadTest extends TestCase
{
    #[DataProvider('localeProvider')]
    public function testResolveLocale(string $language, ?string $expected): void
    {
        $this->assertSame($expected, Yad::resolveLocale($language));
    }

    public static function localeProvider(): array
    {
        return [
            ['de', 'de'],
            ['de-CH', 'de-CH'],
            ['de-DE', 'de'],
            ['de_ch', 'de-CH'],
            ['pt-br', 'pt-BR'],
            ['pt-PT', 'pt'],
            ['no', 'nb'],
            ['zh', 'zh-CN'],
            ['zh-Hant-TW', 'zh-TW'],
            ['sr-Latn', 'sr-Latn'],
            ['sr-Cyrl', 'sr'],
            ['en', null],
            ['en-US', null],
            ['en-GB', 'en-GB'],
            ['xx', null],
        ];
    }

    public function testEveryLocaleHasAFile(): void
    {
        foreach (Yad::LOCALES as $key) {
            $this->assertFileExists(Yii::getAlias("@npm/sandritsch91--yad/dist/i18n/$key.umd.js"));
        }
    }

    #[DataProvider('timeFormatProvider')]
    public function testConvertTimeIcuToYad(string $icu, string $expected): void
    {
        $this->assertSame($expected, Yad::convertTimeIcuToYad($icu));
    }

    public static function timeFormatProvider(): array
    {
        return [
            ['HH:mm', 'HH:mm'],
            ['H:mm:ss', 'H:mm:ss'],
            ['h:mm a', 'h:mm TT'],
            ['HH:mm:ss.SSS', 'HH:mm:ss.l'],
            ["HH 'h' mm", "HH 'h' mm"],
            ['HH:mm:ss zzzz', 'HH:mm:ss'],
        ];
    }

    public function testInvalidMode(): void
    {
        $this->expectException(InvalidConfigException::class);
        Yad::widget(['name' => 'd', 'mode' => 'week']);
    }

    public function testDateInput(): void
    {
        $html = Yad::widget(['name' => 'd', 'value' => '2026-11-20', 'id' => 'd']);

        $this->assertStringContainsString('name="d"', $html);
        $this->assertStringContainsString('value="20.11.2026"', $html);
        $this->assertStringContainsString('autocomplete="off"', $html);

        $js = $this->registeredJs();
        $this->assertStringContainsString('"dateFormat":"dd.mm.yy"', $js);
        $this->assertStringContainsString('Yad.regional["de-CH"]', $js);
        $this->assertStringContainsString('dp.setDate(new Date(2026, 10, 20, 0, 0, 0));', $js);
        $this->assertSame(['yad.umd.js', 'i18n/de-CH.umd.js'], Yii::$app->view->assetBundles[YadAsset::class]->js);
    }

    public function testModelDatetimeWithSubmitFormat(): void
    {
        $model = new DynamicModel(['start' => '2026-11-20 15:05:00']);
        $model->addRule('start', 'safe');
        $html = Yad::widget([
            'model' => $model,
            'attribute' => 'start',
            'mode' => Yad::MODE_DATETIME,
            'submitFormat' => ['separator' => 'T'],
            'options' => ['id' => 'start'],
        ]);

        $this->assertMatchesRegularExpression('/<input type="text" id="start"[^>]*value="20.11.2026 15:05"/', $html);
        $this->assertStringNotContainsString('type="text" id="start" name=', $html);
        $this->assertMatchesRegularExpression(
            '/<input type="hidden" id="start-value" name="DynamicModel\[start\]" value="2026-11-20T15:05:00"/',
            $html
        );

        $js = $this->registeredJs();
        $this->assertStringContainsString('"showTime":true', $js);
        $this->assertStringContainsString('"timeFormat":"HH:mm"', $js);
        $this->assertStringContainsString('"altField":"#start-value"', $js);
        $this->assertStringContainsString('"altFormat":"yy-mm-dd"', $js);
        $this->assertStringContainsString('"altTimeFormat":"HH:mm:ss"', $js);
        $this->assertStringContainsString('"altSeparator":"T"', $js);
        $this->assertStringContainsString('dp.setDate(new Date(2026, 10, 20, 15, 5, 0));', $js);
    }

    public function testInlineTime(): void
    {
        $html = Yad::widget([
            'name' => 't',
            'value' => '2026-11-20 08:30:00',
            'mode' => Yad::MODE_TIME,
            'inline' => true,
            'id' => 't',
        ]);

        $this->assertStringContainsString('<input type="hidden" id="t" name="t" value="08:30">', $html);
        $this->assertStringContainsString('<div id="t-yad"></div>', $html);

        $js = $this->registeredJs();
        $this->assertStringContainsString('document.getElementById("t-yad")', $js);
        $this->assertStringContainsString('"timeOnly":true', $js);
        $this->assertStringContainsString('"altField":"#t"', $js);
        $this->assertStringContainsString(', 8, 30, 0));', $js);
    }

    public function testClientOptionsEventsAndTheme(): void
    {
        Yad::widget([
            'name' => 'd',
            'id' => 'd',
            'language' => 'en-US',
            'dateFormat' => 'yyyy-MM-dd',
            'theme' => 'auto',
            'bootstrapTheme' => true,
            'clientOptions' => ['minDate' => 0, 'onSelect' => new JsExpression('function () {}')],
            'clientEvents' => ['change' => 'function (e) { console.log(e); }'],
        ]);

        $js = $this->registeredJs();
        $this->assertStringContainsString('"dateFormat":"yy-mm-dd"', $js);
        $this->assertStringContainsString('"theme":"auto"', $js);
        $this->assertStringContainsString('"minDate":0', $js);
        $this->assertStringContainsString('"onSelect":function () {}', $js);
        $this->assertStringContainsString('Yad.regional[""]', $js);
        $this->assertStringContainsString('el.addEventListener("change", function (e) { console.log(e); });', $js);
        $this->assertArrayHasKey(YadBootstrap5Asset::class, Yii::$app->view->assetBundles);
        $this->assertSame(['yad.umd.js'], Yii::$app->view->assetBundles[YadAsset::class]->js);
    }

    public function testEmptyAndUnparsableValues(): void
    {
        $this->assertStringContainsString('value=""', Yad::widget(['name' => 'd', 'value' => null]));
        $this->assertStringContainsString('value="not a date"', Yad::widget(['name' => 'd', 'value' => 'not a date']));
        $this->assertStringNotContainsString('setDate', $this->registeredJs());
    }

    private function registeredJs(): string
    {
        return implode("\n", array_merge(...array_values(Yii::$app->view->js)));
    }
}
