<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\web\assets\provider;


use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class ProviderEnvironmentStatus
 * @package flipbox\saml\core\web\assets\provider
 */
class ProviderEnvironmentStatus extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__;

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->js = [
            'ProviderEnvironmentStatus' . $this->dotJs()
        ];

        parent::init();
    }
}