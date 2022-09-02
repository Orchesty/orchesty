<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller;

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
final class CustomNodeController implements LoggerAwareInterface
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
     * @Route("/custom-node/{id}/process", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    public function sendAction(Request $request, string $id): Response
    {
        try {
            $data = $this->handler->processAction($id, $request);

            return $this->getResponse($data->getData(), 200, ControllerUtils::createHeaders($data->getHeaders()));
        } catch (PipesFrameworkExceptionAbstract | OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponse($e, 500, ControllerUtils::INTERNAL_SERVER_ERROR, $request->headers->all());
        }
    }

    /**
     * @Route("/custom-node/{id}/process/test", methods={"GET", "OPTIONS"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function sendTestAction(string $id): Response
    {
        try {
            $this->handler->processTest($id);

            return $this->getResponse([]);
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @Route("/custom-node/list", methods={"GET"})
     *
     * @return Response
     */
    public function listOfCustomNodesAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getCustomNodes());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

}
