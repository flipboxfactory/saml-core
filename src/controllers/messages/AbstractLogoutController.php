<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\messages;

use craft\db\Table;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\models\AbstractSettings;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\AbstractProviderIdentity;
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

    protected array|bool|int $allowAnonymous = [
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
    public function beforeAction($action): bool
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
    public function actionIndex($uid = null)
    {
        $message = Factory::receive();

        $isRequest = $message instanceof LogoutRequest;
        $isResponse = $message instanceof LogoutResponse;

        if ((! $isRequest && ! $isResponse) ||
            $isResponse && $this->getPlugin()->getSession()->getRequestId() !== $message->getInResponseTo()) {
            throw new HttpException(400, "Invalid request");
        }

        $settings = $this->getPlugin()->getSettings();

        /** @var AbstractProvider $theirProvider */
        $theirProvider = $this->getPlugin()->getProvider()->findByEntityId(
            MessageHelper::getIssuer($message->getIssuer())
        )->one();
        $condition = [
            'enabled' => 1
        ];

        if ($uid) {
            $condition['uid'] = $uid;
        } else {
            $condition['entityId'] = $settings->getEntityId();
        }
        /** @var AbstractProvider $ourProvider */
        $ourProvider = $this->getPlugin()->getProvider()->find($condition)->one();

        if ($isRequest) {
            if (\Craft::$app->getUser()->isGuest) {
                $this->destroySpecifiedSession(
                    $message,
                    $theirProvider,
                    $this->getPlugin()->getSettings()
                );
            }

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

            \Craft::$app->user->logout(true);
            Factory::send($response, $theirProvider);
            \Craft::$app->end();
        }

        return $this->redirect(
            \Craft::$app->config->general->logoutPath
        );
    }

    /**
     * @param LogoutRequest $message
     * @param AbstractProvider $theirProvider
     * @throws \yii\db\Exception
     */
    protected function destroySpecifiedSession(
        LogoutRequest $message,
        AbstractProvider $theirProvider,
        AbstractSettings $settings
    ) {
        if (! $settings->sloDestroySpecifiedSessions) {
            return;
        }

        /** @var AbstractProviderIdentity $user */
        $user = $this->getPlugin()->getProviderIdentity()->findByNameId(
            $message->getNameId()->getValue(),
            $theirProvider
        )->one();

        if ($user) {
            \Craft::$app->getDb()->createCommand()
                ->delete(Table::SESSIONS, [
                    'userId' => $user->userId,
                ])
                ->execute();
        }
    }


    /**
     * @param null $uid
     * @throws \flipbox\saml\core\exceptions\InvalidMetadata
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionRequest($uid = null)
    {
        // Backwards compatibility with 1.0
        // The request shouldn't get here with a SAMLResponse
        if (\Craft::$app->request->getBodyParam('SAMLResponse')) {
            return $this->actionIndex();
        }

        /** @var AbstractProvider $theirProvider */
        $theirProvider = $this->getRemoteProvider($uid);

        /** @var AbstractProvider $ourProvider */
        $ourProvider = $this->getPlugin()->getProvider()->findOwn();

        $user = \Craft::$app->user->getIdentity();

        if (! $user || (
                $user &&
                ! $identity = $this->getPlugin()->getProviderIdentity()->findByUserAndProvider($user, $theirProvider)
            )
        ) {
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

        Factory::send($logoutRequest, $theirProvider);
        \Craft::$app->end();
    }
}
