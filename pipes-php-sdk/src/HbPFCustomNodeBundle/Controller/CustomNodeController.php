<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
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
 * @package Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller
 */
class CustomNodeController extends AbstractFOSRestController implements LoggerAwareInterface
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
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    public function sendAction(Request $request, string $nodeId): Response
    {
        try {
            $data = $this->handler->process($nodeId, (string) $request->getContent(), $request->headers->all());

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (CustomNodeException $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 200, ControllerUtils::createHeaders([], $e));
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
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
     * @Route("/custom_node/list", methods={"GET"})
     *
     * @return Response
     */
    public function listOfCustomNodesAction(): Response
    {
        try {
            $data = $this->handler->getCustomNodes();

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return $this->getErrorResponse($e, 500);
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
