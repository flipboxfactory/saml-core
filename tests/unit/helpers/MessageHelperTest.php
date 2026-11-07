<?php

namespace flipbox\saml\core\tests\unit\helpers;

use Codeception\Test\Unit;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\containers\Saml2Container;
use flipbox\saml\core\helpers\MessageHelper;
use SAML2\AuthnRequest;
use SAML2\Compat\ContainerSingleton;
use SAML2\LogoutRequest;
use SAML2\LogoutResponse;
use SAML2\Message;
use SAML2\Response;
use SAML2\XML\saml\Issuer;

class MessageHelperTest extends Unit
{
    protected function _before(): void
    {
        ContainerSingleton::setContainer(new Saml2Container($this->createMock(AbstractPlugin::class)));
    }

    public function testGenerateIdIsUniqueAndSamlSafe(): void
    {
        $id = MessageHelper::generateId();

        $this->assertSame(MessageHelper::ID_LENGTH, strlen($id));
        // xs:ID must not start with a digit
        $this->assertMatchesRegularExpression('/^_[0-9a-f]+$/', $id);
        $this->assertNotSame($id, MessageHelper::generateId());
    }

    /**
     * @dataProvider messageProvider
     */
    public function testMessageClassification(string $class, bool $isRequest, string $parameter): void
    {
        // Built here, not in the provider: saml2 needs the container, which _before() sets.
        /** @var Message $message */
        $message = new $class();

        $this->assertSame($isRequest, MessageHelper::isRequest($message));
        $this->assertSame(!$isRequest, MessageHelper::isResponse($message));
        $this->assertSame($parameter, MessageHelper::getParameterKeyByMessage($message));
    }

    public function messageProvider(): array
    {
        return [
            'AuthnRequest' => [AuthnRequest::class, true, MessageHelper::REQUEST_PARAMETER],
            'LogoutRequest' => [LogoutRequest::class, true, MessageHelper::REQUEST_PARAMETER],
            'Response' => [Response::class, false, MessageHelper::RESPONSE_PARAMETER],
            'LogoutResponse' => [LogoutResponse::class, false, MessageHelper::RESPONSE_PARAMETER],
        ];
    }

    public function testGetIssuer(): void
    {
        $issuer = new Issuer();
        $issuer->setValue('https://idp.example.com');

        $this->assertSame('https://idp.example.com', MessageHelper::getIssuer($issuer));
        $this->assertSame('https://idp.example.com', MessageHelper::getIssuer('https://idp.example.com'));
        $this->assertNull(MessageHelper::getIssuer(null));
    }
}
