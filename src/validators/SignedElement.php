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
        $errors = [];
        $success = false;
        foreach ($this->xmlSecurityKeyStore as $key) {
            try {
                if ($success = $signedElement->validate($key)) {
                    // return on success ... no need to continue
                    return $result;
                }
            } catch (\Exception $e) {
                $errors[] = $e;
                \Craft::info($e->getMessage(), AbstractPlugin::SAML_CORE_HANDLE);
            }
        }

        if (! empty($errors)) {
            throw $errors[0];
        }

        return $result;
    }
}
