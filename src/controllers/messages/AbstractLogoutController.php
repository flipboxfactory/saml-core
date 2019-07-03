<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\messages;

use craft\web\Controller;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\services\bindings\Factory;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use yii\web\HttpException;

/**
 * Class AbstractLogoutController
 * @package flipbox\saml\core\controllers\messages
 */
abstract class AbstractLogoutController extends AbstractController implements \flipbox\saml\core\EnsureSAMLPlugin
{

    protected $allowAnonymous = [
        'actionIndex',
        'actionRequest',
    ];

    public $enableCsrfValidation = false;

    /**
     * @return ProviderInterface
     */
    abstract protected function getRemoteProvider($uid = null);

    /**
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        if (in_array(
            $action->actionMethod,
            [
            'actionIndex',
            'actionRequest',
            ]
        )) {
            return true;
        }
        return parent::beforeAction($action);
    }


    /**
     * @return \yii\web\Response
     * @throws HttpException
     * @throws \flipbox\saml\core\exceptions\InvalidMetadata
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        $message = Factory::receive();

        $isRequest = $message instanceof LogoutRequest;
        $isResponse = $message instanceof LogoutResponse;

        if ($isResponse && $this->getPlugin()->getSession()->getRequestId() !== $message->getInResponseTo()) {
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

        /** @var AbstractProvider $theirProvider */
        $theirProvider = $this->getPlugin()->getProvider()->findByEntityId(
            MessageHelper::getIssuer($message->getIssuer())
        )->one();

        /** @var AbstractProvider $ourProvider */
        $ourProvider = $this->getPlugin()->getProvider()->findOwn();

        if ($isRequest) {
            /** @var LogoutResponse $response */
            $response = $this->getPlugin()->getLogoutResponse()->create(
                $message,
                $theirProvider,
                $ourProvider
            );

            /**
             * Add the request id to the the response.
             */
            $response->setInResponseTo($message->getId());

            \Craft::$app->user->logout();
            Factory::send($response, $theirProvider);
            \Craft::$app->end();
        }

        return $this->redirect(
            \Craft::$app->config->general->logoutPath
        );
    }


    /**
     * @param null $uid
     * @throws \flipbox\saml\core\exceptions\InvalidMetadata
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRequest($uid = null)
    {

        /** @var AbstractProvider $theirProvider */
        $theirProvider = $this->getRemoteProvider($uid);

        /** @var AbstractProvider $ourProvider */
        $ourProvider = $this->getPlugin()->getProvider()->findOwn();

        $user = \Craft::$app->user->getIdentity();

        if (! $identity = $this->getPlugin()->getProviderIdentity()->findByUserAndProvider($user, $theirProvider)) {
            $saml = $this->getPlugin();
            $saml::warning('Logout not available. User is not logged in.');
            // Logout locally only
            return $this->redirect(
                \Craft::$app->config->general->logoutPath
            );
        }

        $logoutRequest = $this->getPlugin()->getLogoutRequest()->create(
            $theirProvider,
            $ourProvider,
            $identity,
            \Craft::$app->config->general->getPostLogoutRedirect()
        );

        /**
         * Save id to session so we can validate the response.
         */
        $this->getPlugin()->getSession()->setRequestId($logoutRequest->getId());
        \Craft::$app->user->logout(); 
        Factory::send($logoutRequest, $theirProvider);
        \Craft::$app->end();
    }
}
