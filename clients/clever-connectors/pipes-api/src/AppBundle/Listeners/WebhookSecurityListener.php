<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\WebhookController;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class WebhookSecurityListener
 *
 * @package CleverConnectors\AppBundle\Listeners
 */
class WebhookSecurityListener implements EventSubscriberInterface
{

    /**
     * @var ObjectRepository
     */
    private $repo;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var SystemLimitManager
     */
    private $systemLimitManager;

    /**
     * WebhookSecurityListener constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     * @param SystemLimitManager   $systemLimitManager
     */
    function __construct(
        DocumentManager $dm,
        CurlManagerInterface $curl,
        SystemLimitManager $systemLimitManager
    )
    {
        $this->repo               = $dm->getRepository(Webhook::class);
        $this->curl               = $curl;
        $this->systemLimitManager = $systemLimitManager;
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

        if ($inf[0] instanceof WebhookController) {
            $req    = $ev->getRequest();
            $params = [
                'nodeName'     => $req->attributes->get('nodeName'),
                'topologyName' => $req->attributes->get('topologyName'),
                'token'        => $req->attributes->get('token'),
                'userId'       => $req->attributes->get('userId'),
            ];

            /** @var Webhook $res */
            $res = $this->repo->findOneBy([
                'nodeName'     => $params['nodeName'],
                'topologyName' => $params['topologyName'],
            ]);

            if (!$res) {
                throw new CleverConnectorsException(
                    sprintf('Webhook with (nodeName, topologyName) [%s, %s] was not found.',
                        $params['nodeName'], $params['topologyName']),
                    CleverConnectorsException::WEBHOOK_NOT_FOUND
                );
            }

            $ev->getRequest()->headers->set(CMHeaders::createKey(CMHeaders::GUID), $params['userId']);
            $ev->getRequest()->headers->set(CMHeaders::createKey(CMHeaders::TOKEN), $params['token']);
            $ev->getRequest()->headers->set(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), $res->getSystemKey());

            $this->systemLimitManager->addSystemLimitToRequestHeaders($ev->getRequest()->headers);

            $req = new RequestDto('GET', new Uri('https://api.dev.clevermonitor.com/v1.2'));
            $req->setHeaders([
                'Accept'    => 'application/json',
                'X-Api-Key' => sprintf('%s:%s', $params['userId'], $params['token']),
            ]);

            try {
                $req  = $this->curl->send($req);
                $code = $req->getStatusCode();
                $text = '';
            } catch (Exception $e) {
                $code = 400;
                $text = $e->getMessage();
            }

            if ($code != 200) {
                throw new CleverConnectorsException(
                    sprintf(
                        'User [%s] with token [%s] was not found. || ',
                        $params['userId'],
                        $params['token']
                    ) . $text,
                    CleverConnectorsException::USER_TOKEN_NOT_EXISTS
                );
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
