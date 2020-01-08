<?php


namespace flipbox\saml\core\models;

trait JsonModel
{

    /**
     * @return array|mixed
     */
    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * @param string $json
     * @return mixed
     */
    public static function jsonUnserialize(string $json)
    {
        return new static(json_decode($json, true));
    }
}
