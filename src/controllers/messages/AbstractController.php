<?php


namespace flipbox\saml\core\controllers\messages;


use craft\web\Controller;
use flipbox\saml\core\traits\EnsureSamlPlugin;

abstract class AbstractController extends Controller
{
    use EnsureSamlPlugin;

    protected $plugin;

    /**
     * Debugging messages
     */
    protected function logRequest()
    {
        if (! $this->plugin) {
            $this->plugin = $this->getSamlPlugin();
        }

        $request = \Craft::$app->request->getBodyParam('SAMLRequest');
        $response = \Craft::$app->request->getBodyParam('SAMLResponse');

        if (\Craft::$app->config->general->devMode && ($request || $response)) {
            try {
                $this->plugin::info(
                    base64_decode($request ?: $response)
                );

            } catch (\Exception $e) {
                $this->plugin::error($e->getMessage() . ' ' . $e->getTraceAsString());
            }
        }

    }


}