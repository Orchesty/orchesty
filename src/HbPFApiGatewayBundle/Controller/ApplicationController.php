<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApplicationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class ApplicationController extends AbstractController
{

    use ControllerTrait;

    public const SYSTEM_USER = 'orchesty';

    /**
     * ApplicationController constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(private readonly ServiceLocator $locator)
    {}

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/applications/available', methods: ['GET'])]
    public function listOfApplicationsAction(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getApps($request->query->get('exclude', '')));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/applications/installed', methods: ['GET'])]
    public function getUsersApplicationAction(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getUserApps(self::SYSTEM_USER, $request->query->get('exclude', '')));
    }

    /**
     * @param string $key
     *
     * @return Response
     */
    #[Route('/applications/{key}/preview', methods: ['GET'])]
    public function getApplicationAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getApp($key));
    }


    /**
     * @param string $key
     *
     * @return Response
     */
    #[Route('/applications/{key}', methods: ['GET'])]
    public function getApplicationDetailAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getAppDetail($key, self::SYSTEM_USER));
    }

    /**
     * @param string $key
     *
     * @return Response
     */
    #[Route('/applications/{key}', methods: ['POST'])]
    public function installApplicationAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->installApp($key, self::SYSTEM_USER));
    }

    /**
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    #[Route('/applications/{key}', methods: ['PUT'])]
    public function updateApplicationSettingsAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->updateApp($key, self::SYSTEM_USER, $request->request->all()));
    }

    /**
     * @param string $key
     *
     * @return Response
     */
    #[Route('/applications/{key}', methods: ['DELETE'])]
    public function uninstallApplicationAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->uninstallApp($key, self::SYSTEM_USER));
    }

    /**
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    #[Route('/applications/{key}/change-state', methods: ['PUT'])]
    #[Route('/applications/{key}/changeState', methods: ['PUT'])]
    public function changeStateApplicationAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->changeState($key, self::SYSTEM_USER, $request->request->all()));
    }

    /**
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    #[Route('/applications/{key}/password', methods: ['PUT'])]
    public function saveApplicationPasswordAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->updateAppPassword($key, self::SYSTEM_USER, $request->request->all()));
    }

    /**
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    #[Route('/applications/{key}/authorize', methods: ['GET'])]
    public function authorizeApplicationAction(Request $request, string $key): Response
    {
        try {
            //TODO: refactor after ServiceLocatorMS will be done
            $this->locator->authorize($key, self::SYSTEM_USER, (string) $request->query->get('redirect_url'));
        } catch (Exception $e) {
            return new JsonResponse(['Error' => $e->getMessage()], 500);
        }

        return new JsonResponse([]);
    }

    /**
     * @param string $key
     *
     * @return Response
     */
    #[Route('/applications/{key}/sync/list', methods: ['GET'])]
    public function getSynchronousActionsAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->listSyncActions($key));
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $method
     *
     * @return Response
     */
    #[Route('/applications/{key}/sync/{method}', methods: ['GET', 'POST'])]
    public function runSynchronousActionsAction(Request $request, string $key, string $method): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new Response($this->locator->runSyncActions($request, $key, $method));
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    #[Route('/applications/{key}/users/{user}/authorize/token', methods: ['GET'])]
    public function setAuthorizationTokenAction(Request $request, string $key, string $user): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        $url = $this->locator->authorizationToken($key, $user, $request->query->all());

        return new RedirectResponse($url['redirectUrl']);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/applications/authorize/token', methods: ['GET'])]
    public function setAuthorizationTokenQueryAction(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        $url = $this->locator->authorizationQueryToken($request->query->all());

        return new RedirectResponse($url['redirectUrl']);
    }

}
