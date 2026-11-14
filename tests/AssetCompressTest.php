<?php

namespace sandritsch91\yii2\yad\tests;

use sandritsch91\yii2\yad\Yad;
use sandritsch91\yii2\yad\YadAsset;
use Yii;
use yii\console\Application as ConsoleApplication;
use yii\console\controllers\AssetController;
use yii\helpers\FileHelper;

/**
 * An application in English, German, French and Italian that combines its assets with `yii asset/compress`.
 */
class AssetCompressTest extends TestCase
{
    /**
     * @var string
     */
    private $dir;

    protected function setUp(): void
    {
        $this->dir = __DIR__ . '/runtime/compress';
        FileHelper::removeDirectory($this->dir);
        FileHelper::createDirectory("$this->dir/assets");
        FileHelper::createDirectory("$this->dir/static");
        parent::setUp();
    }

    public function testCompressedBundleContainsTheConfiguredLocales()
    {
        $bundles = $this->compress(['de', 'fr', 'it']);

        $files = glob("$this->dir/static/all-*.js");
        $this->assertCount(1, $files);
        $js = file_get_contents($files[0]);

        $order = [];
        foreach (['yad.umd.js', 'i18n/de.umd.js', 'i18n/fr.umd.js', 'i18n/it.umd.js'] as $file) {
            $position = strpos($js, "$file ***/");
            $this->assertNotFalse($position, "$file is in the compressed file");
            $order[] = $position;
        }
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order, 'yad comes before its locales');
        $this->assertSame(4, substr_count($js, '*** BEGIN FILE:'), 'no other locale is included');

        $this->assertSame([], $bundles[YadAsset::class]['js']);
        $this->assertSame(['all'], $bundles[YadAsset::class]['depends']);
    }

    public function testCompressedLocalesRegisterInTheBrowser()
    {
        $this->compress(['de', 'fr', 'it']);
        $node = $this->findNode();
        $script = <<<'JS'
const vm = require('vm');
const fs = require('fs');
const context = {};
context.window = context.self = context;
vm.createContext(context);
vm.runInContext(fs.readFileSync(process.argv[2], 'utf8'), context);
const regional = context.yad.Yad.regional;
process.stdout.write(JSON.stringify(['de', 'fr', 'it', 'es'].map((key) => regional[key] ? regional[key].monthNames[0] : null)));
JS;
        file_put_contents("$this->dir/check.js", $script);
        $file = glob("$this->dir/static/all-*.js")[0];
        $output = shell_exec(escapeshellarg($node) . ' ' . escapeshellarg("$this->dir/check.js") . ' ' . escapeshellarg($file));

        $this->assertSame(['Januar', 'janvier', 'gennaio', null], json_decode($output, true));
    }

    /**
     * @dataProvider languageProvider
     */
    public function testWidgetUsesTheCompressedBundle($language, $expectedLocaleJs)
    {
        $bundles = $this->compress(['de', 'fr', 'it']);
        $this->mockApplication([
            'language' => $language,
            'components' => ['assetManager' => ['bundles' => $bundles]],
        ]);

        $html = $this->renderPage(Yad::widget(['name' => 'date', 'id' => 'date']));

        $this->assertMatchesRegularExpression('#<script src="/static/all-[^"]+\.js"></script>#', $html);
        $this->assertMatchesRegularExpression('#<link href="/static/all-[^"]+\.css" rel="stylesheet">#', $html);
        $this->assertStringNotContainsString('yad.umd.js', $html);
        $this->assertStringNotContainsString('i18n/', $html);
        $this->assertStringContainsString($expectedLocaleJs, $html);
    }

    public function languageProvider()
    {
        return [
            'English' => ['en-US', 'var locale = Yad.regional[""] || {};'],
            'German' => ['de', 'var locale = Yad.regional["de"] || {};'],
            'French' => ['fr', 'var locale = Yad.regional["fr"] || {};'],
            'Italian' => ['it', 'var locale = Yad.regional["it"] || {};'],
            // regional variants that were not compressed fall back to the language
            'Swiss German' => ['de-CH', 'var locale = Yad.regional["de-CH"] || Yad.regional["de"] || {};'],
            'Swiss French' => ['fr-CH', 'var locale = Yad.regional["fr-CH"] || Yad.regional["fr"] || {};'],
            'Swiss Italian' => ['it-CH', 'var locale = Yad.regional["it-CH"] || Yad.regional["it"] || {};'],
        ];
    }

    public function testWithoutCompressionLocalesAreStillLoadedOnDemand()
    {
        $this->mockApplication(['language' => 'it']);

        $html = $this->renderPage(Yad::widget(['name' => 'date', 'id' => 'date']));

        $this->assertMatchesRegularExpression('#<script src="/assets/[^"]+/yad.umd.js"></script>\s*<script src="/assets/[^"]+/i18n/it.umd.js"></script>#', $html);
    }

    /**
     * Runs `yii asset/compress` for [[YadAsset]] with the given locales.
     *
     * @param string[] $locales
     * @return array the generated bundle configuration for the web application
     */
    private function compress(array $locales)
    {
        $webApp = Yii::$app;
        $dir = $this->dir;
        $config = [
            'bundles' => [YadAsset::class],
            'targets' => [
                'all' => [
                    'class' => 'yii\web\AssetBundle',
                    'basePath' => "$dir/static",
                    'baseUrl' => '/static',
                    'js' => 'all-{hash}.js',
                    'css' => 'all-{hash}.css',
                ],
            ],
            'assetManager' => [
                'basePath' => "$dir/assets",
                'baseUrl' => '/assets',
                'bundles' => [
                    YadAsset::class => ['locales' => $locales],
                ],
            ],
        ];
        // compressors are callables in real configs too; plain concatenation keeps the test free of Java tools
        $configFile = "$dir/config.php";
        file_put_contents($configFile, '<?php return array_merge(' . var_export($config, true) . ', [
            "jsCompressor" => function ($controller, $files, $output) { $controller->combineJsFiles($files, $output); },
            "cssCompressor" => function ($controller, $files, $output) { $controller->combineCssFiles($files, $output); },
        ]);');

        new ConsoleApplication([
            'id' => 'console',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'aliases' => ['@npm' => dirname(__DIR__) . '/vendor/npm-asset'],
        ]);
        $controller = new SilentAssetController('asset', Yii::$app);
        $controller->actionCompress($configFile, "$dir/bundles.php");
        Yii::$app = $webApp;

        return require "$dir/bundles.php";
    }

    private function renderPage($content)
    {
        $view = Yii::$app->view;
        file_put_contents("$this->dir/page.php", '<?php $this->beginPage(); ?><html><head><?php $this->head(); ?></head><body><?php $this->beginBody(); ?><?= $content ?><?php $this->endBody(); ?></body></html><?php $this->endPage(); ?>');

        return $view->renderFile("$this->dir/page.php", ['content' => $content]);
    }

    private function findNode()
    {
        $command = DIRECTORY_SEPARATOR === '\\' ? 'where node 2>NUL' : 'command -v node 2>/dev/null';
        $node = trim((string)strtok((string)shell_exec($command), "\r\n"));
        if ($node === '') {
            $this->markTestSkipped('Node.js is needed to run the compressed bundle.');
        }

        return $node;
    }
}

/**
 * Keeps the compressor's progress messages out of the test output.
 */
class SilentAssetController extends AssetController
{
    public function stdout($string)
    {
        return false;
    }
}
