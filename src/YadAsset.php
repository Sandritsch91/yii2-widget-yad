<?php

namespace sandritsch91\yii2\yad;

use yii\web\AssetBundle;

/**
 * Registers the yad UMD build (`window.yad`) and its base stylesheet.
 *
 * Locales are added on demand with [[addLocale()]], so a page only downloads the languages it uses.
 */
class YadAsset extends AssetBundle
{
    /**
     * {@inheritdoc}
     */
    public $sourcePath = '@npm/sandritsch91--yad/dist';

    /**
     * {@inheritdoc}
     */
    public $js = [
        'yad.umd.js',
    ];

    /**
     * {@inheritdoc}
     */
    public $css = [
        'yad.css',
    ];

    /**
     * @var string[] locales that are always part of this bundle, e.g. `['de', 'fr', 'it']`.
     *
     * Without it, the widget adds the locale of the current language on demand. Set it for
     * `yii asset/compress`, so the locales end up in the compressed file:
     *
     * ```php
     * 'assetManager' => [
     *     'bundles' => [
     *         sandritsch91\yii2\yad\YadAsset::class => ['locales' => ['de', 'fr', 'it']],
     *     ],
     * ],
     * ```
     */
    public array $locales = [];

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        foreach ($this->locales as $key) {
            $this->addLocale($key);
        }
    }

    /**
     * Adds the UMD file of a locale. It registers itself on `yad.Yad.regional` once loaded.
     *
     * Does nothing when the bundle no longer contains `yad.umd.js`, e.g. after `yii asset/compress` replaced it
     * with a combined file: the locales are then expected in that file (see [[locales]]).
     *
     * @param string $key a locale key as returned by [[Yad::resolveLocale()]], e.g. `de` or `pt-BR`
     */
    public function addLocale(string $key): static
    {
        if (!in_array('yad.umd.js', $this->js, true)) {
            return $this;
        }
        $file = "i18n/$key.umd.js";
        if (!in_array($file, $this->js, true)) {
            $this->js[] = $file;
        }

        return $this;
    }
}
