<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\PluginsController;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Plugins\PluginsSecurityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PluginsSecurityListener
 *
 * @package CleverConnectors\AppBundle\Listeners
 */
class PluginsSecurityListener implements EventSubscriberInterface
{

    /**
     * @var PluginsSecurityManager
     */
    private $security;

    /**
     * PluginSecurityListener constructor.
     *
     * @param PluginsSecurityManager $security
     */
    function __construct(PluginsSecurityManager $security)
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

        if ($inf[0] instanceof PluginsController) {
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
