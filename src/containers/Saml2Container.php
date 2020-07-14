<?php


namespace flipbox\saml\core\containers;

use craft\web\Response;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\EnsureSAMLPlugin;
use flipbox\saml\core\helpers\MessageHelper;
use flipbox\saml\core\helpers\SerializeHelper;
use SAML2\Compat\AbstractContainer;
use flipbox\craft\psr3\Logger;

class Saml2Container extends AbstractContainer implements EnsureSAMLPlugin
{

    const TEMPLATE_PATH = 'saml-core/_components/post-binding-submit.twig';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;


    /**
     * @var AbstractPlugin
     */
    protected $plugin;

    /**
     * Create a new SimpleSAMLphp compatible container.
     */
    public function __construct(AbstractPlugin $plugin)
    {
        $this->logger = new Logger([
            'category' => 'saml-core',
        ]);
        $this->plugin = $plugin;
    }

    public function getPlugin(): AbstractPlugin
    {
        return $this->plugin;
    }


    /**
     * {@inheritdoc}
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): \Psr\Log\LoggerInterface
    {
        return $this->logger;
    }


    /**
     * {@inheritdoc}
     * @return string
     */
    public function generateId(): string
    {
        return MessageHelper::generateId();
    }


    /**
     * {@inheritdoc}
     * @return void
     */
    public function debugMessage($message, $type): void
    {
        if ($message instanceof \DOMDocument || $message instanceof \DOMElement) {
            $message = $message->ownerDocument->saveXML();
        }

        $this->getLogger()->debug($message, ['type' => $type]);
    }


    /**
     * {@inheritdoc}
     * @param string $url
     * @param array $data
     * @return void
     */
    public function redirect($url, $data = []): void
    {

        $url = SerializeHelper::redirectUrl($url, $data);

        \Craft::$app->response->redirect($url);

        \Craft::$app->end();
    }


    /**
     * {@inheritdoc}
     * @param string $url
     * @param array $data
     * @return void
     */
    public function postRedirect($url, $data = []): void
    {

        $data['destination'] = $url;

        if (!isset($data['RelayState'])) {
            $data['RelayState'] = '';
        }

        $view = \Craft::$app->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        \Craft::$app->response->data = $view->renderTemplate(
            $this->getTemplatePath(),
            $data
        );
        \Craft::$app->response->format = Response::FORMAT_HTML;
        \Craft::$app->response->send();
        \Craft::$app->end();
    }

    /**
     * @inheritDoc
     */
    public function getTempDir() : string
    {
        $tempDir = CRAFT_STORAGE_PATH . DIRECTORY_SEPARATOR . 'saml2';
        if (!file_exists($tempDir)) {
            mkdir($tempDir);
        }

        return $tempDir;
    }


    /**
     * @inheritDoc
     */
    public function writeFile(string $filename, string $data, int $mode = null) : void
    {
        if ($mode === null) {
            $mode = 660;
        }
        file_put_contents(
            $filename,
            $data
        );
        chmod(
            $filename,
            $mode
        );
    }

    /**
     * SAML Plugin Utils
     */

    /**
     * @return string
     */
    protected function getTemplatePath()
    {
        return static::TEMPLATE_PATH;
    }
}
