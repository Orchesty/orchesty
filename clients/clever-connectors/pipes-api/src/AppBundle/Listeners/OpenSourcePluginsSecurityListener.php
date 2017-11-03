<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\OpenSourcePluginsController;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\OpenSourcePlugins\OpenSourcePluginsSecurityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class OpenSourcePluginsSecurityListener
 *
 * @package CleverConnectors\AppBundle\Listeners
 */
class OpenSourcePluginsSecurityListener implements EventSubscriberInterface
{

    /**
     * @var OpenSourcePluginsSecurityManager
     */
    private $security;

    /**
     * PluginSecurityListener constructor.
     *
     * @param OpenSourcePluginsSecurityManager $security
     */
    function __construct(OpenSourcePluginsSecurityManager $security)
    {
        $this->security = $security;
    }

    /**
     * @param FilterControllerEvent $ev
     *
     * @throws CleverConnectorsException
     */
    public function checkSecurity(FilterControllerEvent $ev): void
    {
        $inf = $ev->getController();
        if (!is_array($inf)) {
            return;
        }

        if ($inf[0] instanceof OpenSourcePluginsController) {
            $headers = $ev->getRequest()->headers;
            $this->security->checkSystemInstall($headers->all());
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'checkSecurity',
        ];
    }

}
