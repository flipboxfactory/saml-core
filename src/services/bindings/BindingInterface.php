<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/31/18
 * Time: 8:11 PM
 */

namespace flipbox\saml\core\services\bindings;


use craft\web\Request;
use flipbox\saml\core\exceptions\InvalidIssuer;
use flipbox\saml\core\models\ProviderInterface;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Protocol\SamlMessage;

/**
 * Interface BindingInterface
 * @package flipbox\saml\core\services\bindings
 */
interface BindingInterface
{

    /**
     * @param SamlMessage $message
     * @return void
     */
    public function send(SamlMessage $message);

    /**
     * @param Request $request
     * @return SamlMessage
     */
    public function receive(Request $request);

    /**
     * @param Issuer $issuer
     * @return ProviderInterface
     * @throws InvalidIssuer
     */
    public function getProviderByIssuer(Issuer $issuer): ProviderInterface;

}