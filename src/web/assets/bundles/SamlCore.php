<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\web\assets\bundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SamlCore extends AssetBundle
{

    public $sourcePath = '@flipbox/saml/core/web/assets';

    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->css = [
            'css/saml-core.css',
        ];

        $this->js = [
            'js/saml-core.js',
        ];

        parent::init();
    }
}
