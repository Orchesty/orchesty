<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Controller\WebhookController;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Exception;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
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
     * @var DocumentRepository
     */
    private $repo;

    /**
     * @var CurlManagerInterface
     */
    private $curl;
    /**
     * @var array
     */
    private $secret;

    /**
     * WebhookSecurityListener constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     * @param array                $secret
     */
    function __construct(DocumentManager $dm, CurlManagerInterface $curl, array $secret)
    {
        $this->repo   = $dm->getRepository(Webhook::class);
        $this->curl   = $curl;
        $this->secret = $secret;
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

            $req = new RequestDto('GET', new Uri('https://api.dev.clevermonitor.com/v1.2'));
            $req->setHeaders([
                'Accept'    => 'application/json',
                'X-Api-Key' => sprintf('%s:%s', $params['userId'], $params['token']),
            ]);
            try {
                $req = $this->curl->send($req, [
                    RequestOptions::CERT    => $this->secret['cert'],
                    RequestOptions::SSL_KEY => $this->secret['cert'],
                    RequestOptions::VERIFY  => $this->secret['ca'],
                ]);

                $req  = $req->getStatusCode();
                $text = '';
            } catch (Exception $e) {
                $req  = 400;
                $text = $e->getMessage();
            }

            if ($req != 200) {
                throw new CleverConnectorsException(
                    sprintf('User [%s] with token [%s] was not found. || ' . $text,
                        $params['userId'], $params['token']),
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
