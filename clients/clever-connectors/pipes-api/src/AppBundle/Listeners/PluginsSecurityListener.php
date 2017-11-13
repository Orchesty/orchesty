<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\PluginsController;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Plugins\PluginsSecurityManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
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
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * PluginSecurityListener constructor.
     *
     * @param PluginsSecurityManager $security
     * @param CurlManagerInterface   $curlManager
     */
    function __construct(PluginsSecurityManager $security, CurlManagerInterface $curlManager)
    {
        $this->security    = $security;
        $this->curlManager = $curlManager;
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

        $eventReq = $ev->getRequest();

        if ($inf[0] instanceof PluginsController) {
            if ($inf[1] == 'installAction') {

                $userId = $eventReq->attributes->get('userId');
                $token  = $eventReq->attributes->get('token');

                $req = new RequestDto('GET', new Uri('https://api.dev.clevermonitor.com/v1.2'));
                $req->setHeaders([
                    'Accept'    => 'application/json',
                    'X-Api-Key' => sprintf('%s:%s', $userId, $token),
                ]);

                try {
                    $req  = $this->curlManager->send($req);
                    $code = $req->getStatusCode();
                    $text = '';
                } catch (Exception $e) {
                    $code = 400;
                    $text = $e->getMessage();
                }

                if ($code != 200) {
                    throw new CleverConnectorsException(
                        sprintf('User [%s] with token [%s] was not found. || ' . $text, $userId, $token),
                        CleverConnectorsException::USER_TOKEN_NOT_EXISTS
                    );
                }

            } else {
                $this->security->checkSystemInstall($eventReq->headers->all());
            }
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
