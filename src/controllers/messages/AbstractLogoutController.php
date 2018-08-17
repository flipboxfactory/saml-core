<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\messages;

use craft\web\Controller;
use craft\web\Request;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\StatusResponse;
use yii\web\HttpException;

abstract class AbstractLogoutController extends Controller
{
    use EnsureSamlPlugin;

    protected $allowAnonymous = [
        'actionIndex',
    ];

    public $enableCsrfValidation = false;

    /**
     * @return ProviderInterface
     */
    abstract protected function getRemoteProvider($uid = null): ProviderInterface;

    /**
     * @param AbstractRequest $samlMessage
     * @param ProviderInterface $provider
     */
    abstract protected function send(SamlMessage $samlMessage, ProviderInterface $provider);

    /**
     * @param Request $request
     * @return StatusResponse
     */
    abstract protected function receive(Request $request): StatusResponse;

    /**
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        if ($action->actionMethod === 'actionIndex') {
            return true;
        }
        return parent::beforeAction($action);
    }


    /**
     * @return \yii\web\Response
     * @throws HttpException
     * @throws \flipbox\saml\core\exceptions\InvalidIssuer
     * @throws \yii\base\ExitException
     */
    public function actionIndex()
    {
        /** @var Request $request */
        $request = \Craft::$app->request;

        if (false === ($request instanceof Request)) {
            throw new HttpException(400, 'Must be a web request.');
        }
        $message = $this->receive($request);

        $isRequest = $message instanceof LogoutRequest;
        $isResponse = $message instanceof LogoutResponse;

        if ($isResponse && $this->getSamlPlugin()->getSession()->getRequestId() !== $message->getInResponseTo()) {
            throw new HttpException(400, "Invalid request");
        }

        /**
         * I guess we shouldn't be here. Just follow the normal logout.
         */
        if (\Craft::$app->getUser()->isGuest) {
            return $this->redirect(
                \Craft::$app->config->general->logoutPath
            );
        }

        /** @var Issuer $issuer */
        $issuer = $message->getIssuer();

        /** @var ProviderInterface $provider */
        $provider = $this->getSamlPlugin()->getHttpPost()->getProviderByIssuer($issuer);

        if ($isRequest) {
            /** @var AbstractRequest $message */
            $response = $this->getSamlPlugin()->getLogoutResponse()->create($message);

            /**
             * Add the request id to the the response.
             */
            $response->setInResponseTo($message->getID());

            $this->send($response, $provider);
            \Craft::$app->end();
        }

        return $this->redirect(
            \Craft::$app->config->general->logoutPath
        );
    }


    /**
     * @throws \yii\base\ExitException
     */
    public function actionRequest($uid = null)
    {

        /** @var ProviderInterface $provider */
        $provider = $this->getRemoteProvider($uid);

        $logoutRequest = $this->getSamlPlugin()->getLogoutRequest()->create($provider);

        /**
         * Save id to session so we can validate the response.
         */
        $this->getSamlPlugin()->getSession()->setRequestId($logoutRequest->getID());

        $this->send($logoutRequest, $provider);
        \Craft::$app->end();
    }
}
