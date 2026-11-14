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
     * Adds the UMD file of a locale. It registers itself on `yad.Yad.regional` once loaded.
     *
     * @param string $key a locale key as returned by [[Yad::resolveLocale()]], e.g. `de` or `pt-BR`
     * @return $this
     */
    public function addLocale($key)
    {
        $file = "i18n/$key.umd.js";
        if (!in_array($file, $this->js, true)) {
            $this->js[] = $file;
        }

        return $this;
    }
}
