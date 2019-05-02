<?php


namespace flipbox\saml\core\containers;

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
        $this->logger = new Logger();
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
    public function getLogger()
    {
        return $this->logger;
    }


    /**
     * {@inheritdoc}
     * @return string
     */
    public function generateId()
    {
        return MessageHelper::generateId();
    }


    /**
     * {@inheritdoc}
     * @return void
     */
    public function debugMessage($message, $type)
    {
        if($message instanceof \DOMDocument || $message instanceof \DOMElement) {
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
    public function redirect($url, $data = [])
    {

        $url = SerializeHelper::redirectUrl($url, $data);

        \Craft::$app->response->redirect($url);

        // show a minimal web page with a clickable link to the URL
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"';
        echo ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\n";
        echo '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";
        echo "  <head>\n";
        echo '    <meta http-equiv="content-type" content="text/html; charset=utf-8">' . "\n";
        echo '    <meta http-equiv="refresh" content="0;URL=\'' . htmlspecialchars($url) . '\'">' . "\n";
        echo "    <title>Redirect</title>\n";
        echo "  </head>\n";
        echo "  <body>\n";
        echo "    <h1>Redirect</h1>\n";
        echo '      <p>You were redirected to: <a id="redirlink" href="' . htmlspecialchars($url) . '">';
        echo htmlspecialchars($url) . "</a>\n";
        echo '        <script type="text/javascript">document.getElementById("redirlink").focus();</script>' . "\n";
        echo "      </p>\n";
        echo "  </body>\n";
        echo '</html>';

        \Craft::$app->end();
    }


    /**
     * {@inheritdoc}
     * @param string $url
     * @param array $data
     * @return void
     */
    public function postRedirect($url, $data = [])
    {

        $data['destination'] = $url;

        $view = \Craft::$app->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        \Craft::$app->response->data = $view->renderTemplate(
            $this->getTemplatePath(),
            $data
        );
        \Craft::$app->response->send();
        \Craft::$app->end();
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