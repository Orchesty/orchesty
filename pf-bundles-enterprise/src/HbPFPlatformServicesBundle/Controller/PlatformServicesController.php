<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\Controller;

use Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\Handler\PlatformServicesHandler;
use Hanaboso\PipesFrameworkEnterprise\PlatformServices\Exception\QuotaExceededException;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class PlatformServicesController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFPlatformServicesBundle\Controller
 */
final class PlatformServicesController extends AbstractController
{

    use ControllerTrait;

    /**
     * PlatformServicesController constructor.
     *
     * @param PlatformServicesHandler $handler
     */
    public function __construct(private readonly PlatformServicesHandler $handler)
    {
    }

    /**
     * @return Response
     */
    #[Route('/platform-services', methods: ['GET'])]
    public function listBindingsAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getBindings());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $serviceType
     *
     * @return Response
     */
    #[Route('/platform-services/{serviceType}', methods: ['PUT'])]
    public function setBindingAction(Request $request, string $serviceType): Response
    {
        try {
            $data = $request->request->all();
            ControllerUtils::checkParameters(['applicationKey', 'sdk'], $data);

            return $this->getResponse(
                $this->handler->setBinding(
                    $serviceType,
                    $request->request->getString('applicationKey'),
                    $request->request->getString('sdk'),
                    $request->request->getString('user', 'system'),
                ),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $serviceType
     *
     * @return Response
     */
    #[Route('/platform-services/{serviceType}', methods: ['DELETE'])]
    public function removeBindingAction(string $serviceType): Response
    {
        try {
            $this->handler->removeBinding($serviceType);

            return $this->getResponse([]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $serviceType
     * @param string  $method
     *
     * @return Response
     */
    #[Route('/platform-services/{serviceType}/call/{method}', methods: ['POST'])]
    public function callAction(Request $request, string $serviceType, string $method): Response
    {
        try {
            return $this->getResponse(
                $this->handler->call($serviceType, $method, $request->request->all()),
            );
        } catch (QuotaExceededException $e) {
            // 429 with structured payload so trace bridge (Go) can lift it
            // straight into a TypeQuotaExceeded WS message.
            return $this->getResponse($e->toPayload(), Response::HTTP_TOO_MANY_REQUESTS);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * Snapshot of the Trace cloud-relay quota state for the Settings/TraceTab
     * UI: live "X / 100 used today" badge plus the source-of-truth `mode`
     * indicator (user override vs system default vs feature disabled).
     *
     * @return Response
     */
    #[Route('/platform-services/trace-ai-provider/quota', methods: ['GET'])]
    public function getTraceQuotaAction(): Response
    {
        try {
            return $this->getResponse($this->handler->getTraceQuotaStatus());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
