<?php


namespace flipbox\saml\core\validators;

use flipbox\saml\core\AbstractPlugin;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\SignedElement as SamlSignedElement;

class SignedElement
{
    /**
     * @var XMLSecurityKey[]
     */
    private $xmlSecurityKeyStore;

    /**
     * SignedElement constructor.
     * @param XMLSecurityKey[] $xmlSecurityKeyStore
     */
    public function __construct(array $xmlSecurityKeyStore)
    {
        $this->xmlSecurityKeyStore = $xmlSecurityKeyStore;
    }


    public function validate(SamlSignedElement $signedElement, $result)
    {
        /** @var \Exception $error */
        $error = null;
        $success = false;
        foreach ($this->xmlSecurityKeyStore as $key) {
            $success = false;
            try {
                $success = $signedElement->validate($key);
            } catch (\Exception $e) {
                $error = $e;
                \Craft::info($e->getMessage(), AbstractPlugin::SAML_CORE_HANDLE);
            }
        }

        if (false === $success && ! is_null($error)) {
            throw $e;
        }

        return $result;
    }
}
