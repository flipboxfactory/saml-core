<?php

declare(strict_types=1);

namespace flipbox\saml\core\controllers\messages;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Url;
use flipbox\saml\core\exceptions\InvalidSignature;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\helpers\SignatureVerifier;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\services\bindings\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

use function CraftCms\Cms\craftAuth;
use function CraftCms\Cms\currentUserElement;

/**
 * Single logout (`{prefix}/logout`, `{prefix}/logout/request`).
 *
 * Incoming LogoutRequests and LogoutResponses are only acted on when they come from a known, enabled partner, are
 * signed by a key in that partner's metadata, and name this endpoint as their Destination. A LogoutResponse must also answer the request this session sent.
 */
abstract class AbstractLogoutController extends AbstractController
{
    /**
     * The partner to log out from, by UID, or the default one.
     */
    abstract protected function getRemoteProvider(?string $uid = null): ?AbstractProvider;

    /**
     * Receives a LogoutRequest or LogoutResponse from a partner.
     */
    public function index(Request $request, ?string $uid = null): mixed
    {
        try {
            $message = Factory::receive($request);
        } catch (Throwable $e) {
            throw new BadRequestHttpException('Invalid SAML message.', $e);
        }

        if (! $message instanceof LogoutRequest && ! $message instanceof LogoutResponse) {
            throw new BadRequestHttpException('Invalid SAML message.');
        }

        // SAML bindings 3.4.5.2/3.5.5.2: a signed message names where it was sent; it must be here
        $destination = (string) $message->getDestination();
        if ($destination === '' || rtrim($destination, '/') !== rtrim($request->url(), '/')) {
            throw new BadRequestHttpException('The logout message wasn’t sent to this endpoint.');
        }

        $theirProvider = $this->findIssuer($message);

        try {
            SignatureVerifier::verify($message, $theirProvider);
        } catch (InvalidSignature) {
            throw new BadRequestHttpException('The logout message isn’t signed by the provider.');
        }

        if ($message instanceof LogoutResponse) {
            $session = $this->getPlugin()->getSession();
            $requestId = $session->getRequestId();

            if (! $requestId || ! hash_equals($requestId, (string) $message->getInResponseTo())) {
                throw new BadRequestHttpException('The logout response doesn’t match a logout request from this session.');
            }

            $session->setRequestId(null);

            return new RedirectResponse($this->logoutUrl());
        }

        $ourProvider = $this->findOwnProvider($uid);

        if ($this->getPlugin()->getSettings()->sloDestroySpecifiedSessions) {
            $this->destroySpecifiedSessions($message, $theirProvider);
        }

        $this->logoutCurrentUser($request);

        Factory::send($this->getPlugin()->getLogoutResponse()->create($message, $theirProvider, $ourProvider), $theirProvider);
    }

    /**
     * Starts a logout with a partner, for the current user (`{prefix}/logout/request[/{externalUid}[/{internalUid}]]`).
     */
    public function request(Request $request, ?string $externalUid = null, ?string $internalUid = null): mixed
    {
        // A partner may post its LogoutResponse here
        if ($request->has('SAMLResponse')) {
            return $this->index($request);
        }

        $user = currentUserElement();
        $theirProvider = $this->getRemoteProvider($externalUid);
        $identity = $user && $theirProvider
            ? $this->getPlugin()->getProviderIdentity()->findByUserAndProvider($user, $theirProvider)
            : null;

        if (! $identity) {
            $this->getPlugin()::warning('Logout not available: the user isn’t logged in with the provider.');

            return new RedirectResponse($this->logoutUrl());
        }

        $ourProvider = $internalUid
            ? $this->getPlugin()->getProvider()->find(['uid' => $internalUid, 'enabled' => true])->first()
            : $this->getPlugin()->getProvider()->findOwn();

        if (! $ourProvider) {
            throw new BadRequestHttpException('This system’s provider isn’t configured.');
        }

        $logoutRequest = $this->getPlugin()->getLogoutRequest()->create(
            $theirProvider,
            $ourProvider,
            $identity,
            (string) Cms::config()->getPostLogoutRedirect(),
        );

        $this->getPlugin()->getSession()->setRequestId($logoutRequest->getId());

        Factory::send($logoutRequest, $theirProvider);
    }

    /**
     * The enabled partner that issued the message.
     */
    protected function findIssuer(LogoutRequest|LogoutResponse $message): AbstractProvider
    {
        $entityId = MessageHelper::getIssuer($message->getIssuer());

        $provider = $entityId ? $this->getPlugin()->getProvider()->find([
            'entityId' => $entityId,
            'providerType' => $this->getPlugin()->getRemoteType(),
            'enabled' => true,
        ])->first() : null;

        return $provider ?? throw new BadRequestHttpException('The logout message isn’t from a known provider.');
    }

    protected function findOwnProvider(?string $uid): AbstractProvider
    {
        $provider = $uid
            ? $this->getPlugin()->getProvider()->find(['uid' => $uid, 'enabled' => true])->first()
            : $this->getPlugin()->getProvider()->findOwn();

        return $provider ?? throw new BadRequestHttpException('This system’s provider isn’t configured.');
    }

    /**
     * Ends every session of the user the (verified) LogoutRequest names (`sloDestroySpecifiedSessions`). Only
     * reaches other devices with the database session driver.
     */
    protected function destroySpecifiedSessions(LogoutRequest $message, AbstractProvider $theirProvider): void
    {
        $nameId = $message->getNameId()?->getValue();
        if (! $nameId) {
            return;
        }

        $identity = $this->getPlugin()->getProviderIdentity()->findByNameId($nameId, $theirProvider)->first();

        if ($identity && $user = $identity->getUser()) {
            Users::invalidateUserSessions($user);
        }
    }

    protected function logoutCurrentUser(Request $request): void
    {
        if (craftAuth()->check()) {
            craftAuth()->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    protected function logoutUrl(): string
    {
        $path = Cms::config()->getLogoutPath();

        return Url::siteUrl(is_string($path) && $path !== '' ? $path : '');
    }
}
