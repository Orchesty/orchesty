<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Exception;
use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Hanaboso\PipesFramework\HbPFUsageStatsBundle\Handler\UsageStatsHandler;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class ApplicationController
 *
 * @package Hanaboso\Applinth\Controller
 */
#[Route('/application')]
final class ApplicationController extends AbstractController
{

    // phpcs:disable SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing.IncorrectLinesCountBetweenAttributeAndTarget

    use ControllerTrait;

    /**
     * ApplicationController constructor.
     *
     * @param EndUserAuthenticator $authenticator
     * @param UsageStatsHandler    $usageStatsHandler
     * @param ServiceLocator       $locator
     * @param TopologyHandler      $topologyHandler
     */
    public function __construct(
        private readonly EndUserAuthenticator $authenticator,
        private readonly UsageStatsHandler $usageStatsHandler,
        private readonly ServiceLocator $locator,
        private readonly TopologyHandler $topologyHandler,
    )
    {
    }

    /**
     * @param string $sdk
     *
     * @return Response
     */
    #[Route('/installed', methods: ['GET'])]
    public function getInstalledApplications(#[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->getUserApps(
                $this->authenticator->getAuthUser(),
                $sdk,
                $this->authenticator->getRootKey(),
            ),
        );
    }

    /**
     * @param string $sdk
     *
     * @return Response
     */
    #[Route('/available', methods: ['GET'])]
    public function getAvailableApplications(#[MapQueryParameter] string $sdk): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::listOfApplicationsAction',
            [],
            ['sdk' => $sdk, 'exclude' => $this->authenticator->getRootKey()],
        );
    }

    /**
     * @param string $key
     * @param string $sdk
     *
     * @return Response
     */
    #[Route('/{key}/preview', methods: ['GET'])]
    public function getApplicationDetail(string $key, #[MapQueryParameter] string $sdk): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationAction',
            ['key' => $key],
            ['sdk' => $sdk],
        );
    }

    /**
     * @param string $key
     * @param string $sdk
     *
     * @return Response
     * @throws Throwable
     */
    #[Route('/{key}', methods: ['GET'])]
    public function getInstalledApplicationDetail(string $key, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->getAppDetail(
                $key,
                $this->authenticator->getAuthUser(),
                $sdk,
                '%s/api/applinth/application/topologies/%s/nodes/%s/run-by-name',
            ),
        );
    }

    /**
     * @param Request $request
     * @param string  $topologyName
     * @param string  $nodeName
     *
     * @return Response
     */
    #[Route('/topologies/{topologyName}/nodes/{nodeName}/run-by-name', methods: ['POST'])]
    public function runTopology(Request $request, string $topologyName, string $nodeName): Response
    {
        try {
            $user = $this->authenticator->getAuthUser();

            return $this->getResponse(
                $this->topologyHandler->runTopologyByName($topologyName, $nodeName, $request->request->all(), $user),
            );
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $key
     * @param string $sdk
     *
     * @return Response
     */
    #[Route('/{key}', methods: ['POST'])]
    public function installApplication(string $key, #[MapQueryParameter] string $sdk): Response
    {
        $user = $this->authenticator->getAuthUser();
        //TODO: refactor after ServiceLocatorMS will be done
        $resp = new JsonResponse($this->locator->installApp($key, $user, $sdk));

        $this->usageStatsHandler->emitEvent(['event' => EventTypeEnum::INSTALL->value, 'aid' => $key, 'euid' => $user]);

        return $resp;
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $sdk
     *
     * @return Response
     */
    #[Route('/{key}', methods: ['PUT'])]
    public function updateApplication(Request $request, string $key, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->updateApp(
                $key,
                $this->authenticator->getAuthUser(),
                $sdk,
                $request->request->all(),
            ),
        );
    }

    /**
     * @param string $key
     * @param string $sdk
     *
     * @return Response
     */
    #[Route('/{key}', methods: ['DELETE'])]
    public function uninstallApplication(string $key, #[MapQueryParameter] string $sdk): Response
    {
        $user = $this->authenticator->getAuthUser();
        //TODO: refactor after ServiceLocatorMS will be done
        $resp = new JsonResponse($this->locator->uninstallApp($key, $this->authenticator->getAuthUser(), $sdk));

        $this->usageStatsHandler->emitEvent(
            ['event' => EventTypeEnum::UNINSTALL->value, 'aid' => $key, 'euid' => $user],
        );

        return $resp;
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $sdk
     *
     * @return Response
     */
    #[Route('/{key}/change-state', methods: ['PUT'])]
    #[Route('/{key}/changeState', methods: ['PUT'])]
    public function changeStateApplication(Request $request, string $key, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->changeState(
                $key,
                $this->authenticator->getAuthUser(),
                $sdk,
                $request->request->all(),
            ),
        );
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $sdk
     *
     * @return Response
     */
    #[Route('/{key}/set-password', methods: ['PUT'])]
    public function setPassword(Request $request, string $key, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->updateAppPassword(
                $key,
                $this->authenticator->getAuthUser(),
                $sdk,
                $request->request->all(),
            ),
        );
    }

    /**
     * @param string $key
     * @param string $sdk
     * @param string $redirectUrl
     *
     * @return Response
     */
    #[Route('/{key}/authorize', methods: ['GET'])]
    public function authorizeApplication(
        string $key,
        #[MapQueryParameter] string $sdk,
        #[MapQueryParameter('redirect_url')] string $redirectUrl,
    ): Response {
        try {
            //TODO: refactor after ServiceLocatorMS will be done
            $this->locator->authorize(
                $key,
                $this->authenticator->getAuthUser(),
                $sdk,
                $redirectUrl,
            );
        } catch (Exception $e) {
            return new JsonResponse(['Error' => $e->getMessage()], 500);
        }

        return new JsonResponse([]);
    }

}
