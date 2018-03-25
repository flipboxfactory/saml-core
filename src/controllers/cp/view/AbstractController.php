<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/9/18
 * Time: 2:48 PM
 */

namespace flipbox\saml\core\controllers\cp\view;


use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\Saml;
use flipbox\saml\core\traits\EnsureSamlPlugin;

abstract class AbstractController extends Controller
{
    use EnsureSamlPlugin;

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp';

    /**
     * @return string
     */
    abstract protected function getProviderRecord();

    /**
     * @return string
     */
    protected function getTemplateIndex()
    {
        return Saml::getTemplateRootKey(
            $this->getSamlPlugin()
        );
    }

    /**
     * @return array
     */
    protected function getBaseVariables()
    {
        return [
            'title'              => $this->getSamlPlugin()->name,
            'pluginHandle'       => $this->getSamlPlugin()->handle,
            'ownEntityId'        => $this->getSamlPlugin()->getSettings()->getEntityId(),

            // Set the "Continue Editing" URL
            'continueEditingUrl' => $this->getBaseCpPath(),
            'baseActionPath'     => $this->getBaseCpPath(),
            'baseCpPath'         => $this->getBaseCpPath(),
        ];
    }

    /**
     * @return string
     */
    protected function getBaseCpPath(): string
    {
        return $this->getSamlPlugin()->getUniqueId();
    }
}