<?php


namespace flipbox\saml\core\models;

use yii\base\Model;

class MetadataOptions extends Model implements \JsonSerializable
{
    use JsonModel;
    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    protected $expiryInterval;

    /**
     * @var \DateTime
     */
    protected $expiryDate;

    /**
     * @param string|null $expiry
     * @return $this
     */
    public function setExpiryInterval($expiry)
    {
        if (! $expiry) {
            $this->expiryDate = null;
            return $this;
        }

        $this->expiryInterval = $expiry;

        $now = new \DateTime();

        if (is_null($this->expiryDate)) {
            $this->expiryDate = $now->add(new \DateInterval($this->expiryInterval));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getExpiryInterval()
    {
        return $this->expiryInterval;
    }

    /**
     * @return \DateTime
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * @param string|null $expiry
     * @return \DateTime
     */
    public function setExpiryDate($expiry)
    {
        if (! $expiry) {
            return $this;
        }

        $this->expiryDate = new \DateTime($expiry);

        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $expiryDate = null;
        if ($this->expiryDate instanceof \DateTime) {
            $expiryDate = $this->expiryDate->format(\DateTime::ISO8601);
        }
        return [
            'expiryDate' => $expiryDate,
            'expiryInterval' => $this->getExpiryInterval(),
            'url' => $this->url,
        ];
    }
}
