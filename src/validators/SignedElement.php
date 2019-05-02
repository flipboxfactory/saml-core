<?php


namespace flipbox\saml\core\validators;


use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\SignedElement as SamlSignedElement;

class SignedElement
{
    /**
     * @var XMLSecurityKey
     */
    private $xmlSecurityKey;

    /**
     * SignedElement constructor.
     * @param XMLSecurityKey $xmlSecurityKey
     */
    public function __construct(XMLSecurityKey $xmlSecurityKey)
    {
        $this->xmlSecurityKey = $xmlSecurityKey;
    }


    public function validate(SamlSignedElement $signedElement, $result)
    {

        $signedElement->validate($this->xmlSecurityKey);

        return $result;
    }

}