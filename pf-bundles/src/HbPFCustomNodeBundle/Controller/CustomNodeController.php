<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCustomNodeBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class CustomNodeController
 *
 * @package Hanaboso\PipesFramework\HbPFCustomNodeBundle\Controller
 */
class CustomNodeController extends FOSRestController implements LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var CustomNodeHandler
     */
    private $handler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CustomNodeController constructor.
     *
     * @param CustomNodeHandler $customNodeHandler
     */
    public function __construct(CustomNodeHandler $customNodeHandler)
    {
        $this->handler = $customNodeHandler;
        $this->logger  = new NullLogger();
    }

    /**
     * @Route("/custom_node/{nodeId}/process", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $nodeId
     *
     * @return Response
     */
    public function sendAction(Request $request, string $nodeId): Response
    {
        try {
            $data = $this->handler->process($nodeId, (string) $request->getContent(), $request->headers->all());

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (CustomNodeException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500, ControllerUtils::createHeaders([], $e));
        }
    }

    /**
     * @Route("/custom_node/{nodeId}/process/test", methods={"GET", "OPTIONS"})
     *
     * @param string $nodeId
     *
     * @return Response
     */
    public function sendTestAction(string $nodeId): Response
    {
        try {
            $this->handler->processTest($nodeId);

            return $this->getResponse([]);
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @required
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}
