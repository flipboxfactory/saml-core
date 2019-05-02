<?php


namespace flipbox\saml\core\validators;

use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\AbstractProvider;
use SAML2\Assertion as SamlAssertion;
use SAML2\Assertion\Validation\Result;
use SAML2\Assertion\Validation\ConstraintValidator;
use SAML2\Configuration\Destination;
use SAML2\Assertion\Validation\AssertionConstraintValidator;
use SAML2\Assertion\Validation\SubjectConfirmationConstraintValidator;
use SAML2\EncryptedAssertion;


class Assertion
{
    /**
     * @var AbstractProvider
     */
    private $identityProvider;
    /**
     * @var AbstractProvider
     */
    private $serviceProvider;
    /**
     * @var \SAML2\Response
     */
    private $response;
    private $validators;

    /**
     * Assertion constructor.
     * @param \SAML2\Response $response
     * @param AbstractProvider $identityProvider
     * @param AbstractProvider $serviceProvider
     * @throws \Exception
     */
    public function __construct(
        \SAML2\Response $response,
        AbstractProvider $identityProvider,
        AbstractProvider $serviceProvider
    )
    {
        $this->identityProvider = $identityProvider;
        $this->serviceProvider = $serviceProvider;
        $this->response = $response;

        $this->addValidators();
    }

    /**
     * @throws \Exception
     */
    private function addValidators()
    {
        $this->validators = [
            new ConstraintValidator\NotBefore(),
            new ConstraintValidator\NotOnOrAfter(),
            new ConstraintValidator\SessionNotOnOrAfter(),
            new ConstraintValidator\SubjectConfirmationMethod(),
            new ConstraintValidator\SubjectConfirmationNotBefore(),
            new ConstraintValidator\SubjectConfirmationNotOnOrAfter(),
            new ConstraintValidator\SubjectConfirmationRecipientMatches(
                new Destination(
                    $this->serviceProvider->firstSpAcsService()->getLocation()
                )
            ),
            new ConstraintValidator\SubjectConfirmationResponseToMatches(
                $this->response
            ),

        ];
        if ($key = $this->identityProvider->signingXMLSecurityKey()) {
            $this->validators[] = new SignedElement($key);
        }
    }

    /**
     * @param $assertion
     * @return Result
     */
    public function validate($assertion): Result
    {
        // Decrypt if needed
        if ($assertion instanceof EncryptedAssertion) {

            // Need to do all of this below
            //\LightSaml\Model\Assertion\EncryptedElementReader::decrypt
            // overwrite assertion with decrypted assertion
            $assertion = $assertion->getAssertion(
//                $this->serviceProvider->keychainPrivateXmlSecurityKey()
                $this->serviceProvider->decryptionKey()
            );
        }

        SerializeHelper::xmlContentType();
        echo $assertion->toXML()->ownerDocument->saveXML();
        exit;

        /** @var SamlAssertion $assertion */

        $result = new Result();

        foreach ($this->validators as $validator) {

            if ($validator instanceof SubjectConfirmationConstraintValidator) {
                $this->validateSubjectConfirmations(
                    $validator,
                    $assertion->getSubjectConfirmation(),
                    $result
                );
            } else {

                /** @var SignedElement|AssertionConstraintValidator $validator */
                $validator->validate($assertion, $result);
            }
        }

        return $result;

    }

    /**
     * @param array $subjectConfirmations
     * @param Result $result
     */
    protected function validateSubjectConfirmations(
        SubjectConfirmationConstraintValidator $validator,
        array $subjectConfirmations,
        Result $result
    )
    {
        foreach ($subjectConfirmations as $subjectConfirmation) {
            $validator->validate($subjectConfirmation, $result);
        }
    }

}