<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class WebhookController
 *
 * @package CleverConnectors\AppBundle\Controller
 */
class WebhookController extends FOSRestController implements LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var StartingPointHandler
     */
    private $handler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * WebhookController constructor.
     *
     * @param StartingPointHandler $handler
     */
    function __construct(StartingPointHandler $handler)
    {
        $this->handler = $handler;
        $this->logger  = new NullLogger();
    }

    /**
     * @Route("/webhook/{userId}/{token}/{nodeName}/{topologyName}", methods={"POST"})
     *
     * @param Request $request
     * @param string  $nodeName
     * @param string  $topologyName
     *
     * @return Response
     */
    public function webhookAction(Request $request, string $nodeName, string $topologyName): Response
    {
        try {
            $this->handler->runWithRequest($request, $topologyName, $nodeName);
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage(), ['Exception' => $t]);
            $this->getErrorResponse($t);
        }

        return $this->getResponse('', 200);
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
