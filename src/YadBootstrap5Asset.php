<?php

namespace sandritsch91\yii2\yad;

use yii\web\AssetBundle;

/**
 * The bundled Bootstrap 5 theme of yad.
 *
 * It takes colours, radii and focus rings from Bootstrap's CSS variables, so it must load after Bootstrap's
 * stylesheet. Bootstrap itself is not registered here: add `yii\bootstrap5\BootstrapAsset` to [[depends]]
 * through the asset manager's `bundles` configuration if your layout does not register it first.
 */
class YadBootstrap5Asset extends AssetBundle
{
    /**
     * {@inheritdoc}
     */
    public $sourcePath = '@npm/sandritsch91--yad/dist/themes';

    /**
     * {@inheritdoc}
     */
    public $css = [
        'bootstrap5.css',
    ];

    /**
     * {@inheritdoc}
     */
    public $depends = [
        YadAsset::class,
    ];
}
