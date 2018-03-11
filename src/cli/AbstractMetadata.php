<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 9:56 PM
 */

namespace flipbox\saml\core\cli;

use craft\helpers\UrlHelper;
use flipbox\keychain\KeyChain;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\SamlPluginInterface;
use yii\console\Controller;
use craft\helpers\Console;
use flipbox\keychain\keypair\traits\OpenSSL;
use flipbox\keychain\keypair\traits\OpenSSLCliUtil;
use flipbox\keychain\records\KeyChainRecord;
use yii\console\ExitCode;

abstract class AbstractMetadata extends Controller
{

    use OpenSSL, OpenSSLCliUtil;

    /**
     * @var bool $force
     * Force save the metadata. If one already exists, it'll be overwritten.
     */
    public $force;

    /**
     * @var int
     * Set the key pair id that you want to use to associate to this record
     */
    public $keyPairId;

    /**
     * @param array $config
     * @return ProviderInterface
     */
    abstract protected function newProviderRecord(array $config): ProviderInterface;

    /**
     * @return SamlPluginInterface
     */
    abstract protected function getSamlPlugin(): SamlPluginInterface;

    /**
     * @var bool
     * Create a new key pair for this server to use to encrypt and sign messages to the remote server
     */
    public $createKeyPair = true;

    public function options($actionID)
    {
        return array_merge(
            [
                'force',
                'keyPairId',
                'createKeyPair',
            ],
            parent::options($actionID)
        );
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return array_merge(
            [
                'f' => 'force',
            ],
            parent::optionAliases()
        );
    }

    /**
     *
     */
    protected function chooseFromKeyChain()
    {

        $keys = [
            '0' => 'Create new key.'
        ];

        foreach (KeyChainRecord::find()->select('id,description')->asArray()->all() as $key) {

            $keys[$key['id']] = sprintf(
                'Existing key (id: %s): %s',
                (string)$key['id'],
                $key['description']
            );
        }

        if (count($keys) > 1) {
            $this->stdout(
                sprintf(
                    'A key must be created or chosen from this list. Choose one of the following or use \'?\' for help.'
                ) . PHP_EOL
            );

            $this->keyPairId = $this->select(
                'Which key',
                $keys
            );
        }

        if (! $this->keyPairId) {
            $this->keyPairId = null;
            $this->createKeyPair = true;
        }


    }

    /**
     * @param null $file
     * @return int
     * @throws \Exception
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function actionCreate()
    {

        $this->newProviderRecord([]);
        if (! $this->keyPairId) {
            $this->chooseFromKeyChain();
        }

        $keyPairRecord = null;
        if ($this->keyPairId === null && $this->createKeyPair) {
            $openSslConfig = $this->promptKeyPair();
            $keyPairRecord = $openSslConfig->create();
            if (! \Craft::$app->getModule(KeyChain::MODULE_ID)->getService()->save($keyPairRecord)) {
                $this->stderr(
                    sprintf('Failed to save new key pair to the database') . PHP_EOL
                    , Console::FG_RED);
                return ExitCode::DATAERR;
            }
        } else if ($this->keyPairId) {
            if (! ($keyPairRecord = KeyChainRecord::findOne([
                'id' => $this->keyPairId,
            ]))) {
                $this->stderr(
                    sprintf('Failed to fetch key pair with id: %s', (string)$this->keyPairId) . PHP_EOL
                    , Console::FG_RED);
                return ExitCode::DATAERR;
            }
        }

        $provider = $this->getSamlPlugin()->getMetadata()->create(
            $keyPairRecord ? $keyPairRecord : null
        );

        if ($this->getSamlPlugin()->getProvider()->save($provider)) {
            $this->getSamlPlugin()->getProvider()->linkToKey(
                $provider,
                $keyPairRecord
            );

            $this->stdout(sprintf(
                    'Save for %s metadata was successful.',
                    $provider->entityId
                ) . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Import a metadata file for an external provider.
     * @param $file
     * Path to file.
     * @param $type
     * This is an enum. Either 'idp' or 'sp'.
     * @return int
     * @throws \Exception
     */
    public function actionImport($file, $type)
    {

        if (! in_array($type, ['idp', 'sp'])) {
            throw new \InvalidArgumentException('Type must be idp or sp');
        }

        $providerClass = $this->getSamlPlugin()->getProvider()->getRecordClass();

        /** @var AbstractProvider $provider */
        $provider = new $providerClass([
            'metadata'     => file_get_contents($file),
            'providerType' => $type,
        ]);

        $provider->getMetadataModel();

        if ($this->getSamlPlugin()->getProvider()->save($provider)) {

            $this->stdout(sprintf(
                    'Save for %s metadata was successful.',
                    $provider->entityId
                ) . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }
        return ExitCode::UNSPECIFIED_ERROR;
    }

    public function actionDelete($entityId)
    {
        if (! Saml::getInstance()->getProvider()->delete(new Provider([
            'entityId' => $entityId,
        ]))) {
            $this->stderr("Couldn't delete provider {$entityId}", Console::FG_RED);
        }


        $this->stdout("Successfully deleted provider {$entityId}" . PHP_EOL, Console::FG_GREEN);
    }
}