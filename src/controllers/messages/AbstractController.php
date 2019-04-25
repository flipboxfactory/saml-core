<?php


namespace flipbox\saml\core\controllers\messages;


use craft\web\Controller;
use flipbox\saml\core\containers\UtilizeSaml2Container;
use flipbox\saml\core\EnsureSAMLPlugin;

abstract class AbstractController extends Controller implements EnsureSAMLPlugin,UtilizeSaml2Container
{

}