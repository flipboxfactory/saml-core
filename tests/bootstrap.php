<?php

// Minimal bootstrap: these tests exercise saml2/xmlseclibs integration and don't need a Craft app or database.
require dirname(__DIR__) . '/vendor/autoload.php';

if (!class_exists('Yii', false)) {
    require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';
}
if (!class_exists('Craft', false)) {
    require dirname(__DIR__) . '/vendor/craftcms/cms/src/Craft.php';
}

// The PSR-3 logger used by Saml2Container reads Craft::$app->log; give it a log dispatcher and nothing else.
Craft::$app = new class() {
    /** @var \yii\log\Dispatcher */
    public $log;

    public function __construct()
    {
        $this->log = new \yii\log\Dispatcher();
    }
};
