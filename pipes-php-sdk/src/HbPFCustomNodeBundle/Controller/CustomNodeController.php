<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller;

use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoControllerTrait;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;
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

    use ProcessDtoControllerTrait;

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
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     * @throws OnRepeatException
     * @throws PipesFrameworkExceptionAbstract
     */
    #[Route('/custom-node/{id}/process', methods: ['POST', 'OPTIONS'])]
    public function sendAction(Request $request, string $id): Response
    {
        try {
            $dto = $this->handler->processAction($id, $request);

            return $this->getResponseFromDto($dto);
        } catch (OnRepeatException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->getErrorResponseFromDto(ProcessDtoFactory::createFromRequest($request), $e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/custom-node/{id}/process/test', methods: ['GET', 'OPTIONS'])]
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
     * @return Response
     */
    #[Route('/custom-node/list', methods: ['GET'])]
    public function listOfCustomNodesAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getCustomNodes());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

}
