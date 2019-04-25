<?php


namespace flipbox\saml\core\containers;


interface UtilizeSaml2Container
{
    protected function getSaml2Container(): Saml2Container;
}