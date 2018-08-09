<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\web\assets\bundles;


use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

/**
 * Class ProviderEnvironmentStatus
 * @package flipbox\saml\core\web\assets\provider
 */
class ProviderEnvironmentStatus extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@flipbox/saml/core/web/assets/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
        VueAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->js = [
            'js/ProviderEnvironmentStatus.js',
        ];

        $this->css = [
            'css/main.css',
        ];

        parent::init();
    }
}