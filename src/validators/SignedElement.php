<?php


namespace flipbox\saml\core\validators;

use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Response\Validation\Result;
use SAML2\SignedElement as SamlSignedElement;

class SignedElement
{
    /**
     * @var XMLSecurityKey
     */
    private $x509Certificate;

    public function __construct(XMLSecurityKey $x509Certificate)
    {
        $this->x509Certificate = $x509Certificate;
    }

    /**
     * @param Response $response
     * @param Result $result
     * @return void
     */
    public function validate(SamlSignedElement $element, $result)
    {

        $element->validate($this->x509Certificate);

    }

}