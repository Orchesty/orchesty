<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class CustomNodeController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller
 */
class CustomNodeController implements LoggerAwareInterface
{

    use ControllerTrait;

    /**
     * @var CustomNodeHandler
     */
    private CustomNodeHandler $handler;

    /**
     * CustomNodeController constructor.
     *
     * @param CustomNodeHandler $customNodeHandler
     */
    public function __construct(CustomNodeHandler $customNodeHandler)
    {
        $this->handler = $customNodeHandler;
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
            $data = $this->handler->process($nodeId, $request);

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
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
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
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
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR);
        }
    }

}
