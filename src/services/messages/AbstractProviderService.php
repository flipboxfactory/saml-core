<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 12:12 AM
 */

namespace flipbox\saml\core\services\messages;


use craft\base\Component;
use craft\helpers\Json;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;

abstract class AbstractProviderService extends Component
{
    /**
     * @param AbstractProvider $record
     * @param bool $runValidation
     * @param null $attributeNames
     * @return AbstractProvider
     * @throws \Exception
     */
    public function save(AbstractProvider $record, $runValidation = true, $attributeNames = null)
    {
        //save record
        if (! $record->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($record->getErrors()));
        }

        return $record;
    }

    /**
     * @param AbstractProvider $provider
     * @param KeyChainRecord $keyChain
     * @param bool $runValidation
     * @param null $attributeNames
     * @throws \Exception
     */
    public function linkToKey(
        AbstractProvider $provider,
        KeyChainRecord $keyChain,
        $runValidation = true,
        $attributeNames = null
    )
    {
        if (! $provider->id && ! $keyChain->id) {
            throw new \Exception('Provider id and keychain id must exist before linking.');
        }
        $linkAttributes = [
            'providerId' => $provider->id,
            'keyChainId' => $keyChain->id,
        ];
        if (! $link = LinkRecord::find()->where($linkAttributes)->one()) {
            $link = new LinkRecord($linkAttributes);
        }
        if (! $link->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($record->getErrors()));
        }
    }

}