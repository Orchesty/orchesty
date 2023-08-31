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
     * @Route("/applications/available", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function listOfApplicationsAction(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getApps($request->query->get('exclude', '')));
    }

    /**
     * @Route("/applications/installed", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getUsersApplicationAction(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getUserApps(self::SYSTEM_USER, $request->query->get('exclude', '')));
    }

    /**
     * @Route("/applications/{key}/preview", methods={"GET", "OPTIONS"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function getApplicationAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getApp($key));
    }


    /**
     * @Route("/applications/{key}", methods={"GET", "OPTIONS"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function getApplicationDetailAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getAppDetail($key, self::SYSTEM_USER));
    }

    /**
     * @Route("/applications/{key}", methods={"POST", "OPTIONS"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function installApplicationAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->installApp($key, self::SYSTEM_USER));
    }

    /**
     * @Route("/applications/{key}", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function updateApplicationSettingsAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->updateApp($key, self::SYSTEM_USER, $request->request->all()));
    }

    /**
     * @Route("/applications/{key}", methods={"DELETE", "OPTIONS"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function uninstallApplicationAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->uninstallApp($key, self::SYSTEM_USER));
    }

    /**
     * @Route("/applications/{key}/changeState", methods={"PUT", "OPTIONS"})
     * @Route("/applications/{key}/change-state", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function changeStateApplicationAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->changeState($key, self::SYSTEM_USER, $request->request->all()));
    }

    /**
     * @Route("/applications/{key}/password", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function saveApplicationPasswordAction(Request $request, string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->updateAppPassword($key, self::SYSTEM_USER, $request->request->all()));
    }

    /**
     * @Route("/applications/{key}/authorize", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
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
     * @Route("/applications/{key}/sync/list", methods={"GET", "OPTIONS"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function getSynchronousActionsAction(string $key): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->listSyncActions($key));
    }

    /**
     * @Route("/applications/{key}/sync/{method}", methods={"GET", "POST"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $method
     *
     * @return Response
     */
    public function runSynchronousActionsAction(Request $request, string $key, string $method): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->runSyncActions($request, $key, $method));
    }

    /**
     * @Route("/applications/{key}/users/{user}/authorize/token", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function setAuthorizationTokenAction(Request $request, string $key, string $user): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        $url = $this->locator->authorizationToken($key, $user, $request->query->all());

        return new RedirectResponse($url['redirectUrl']);
    }

    /**
     * @Route("/applications/authorize/token", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setAuthorizationTokenQueryAction(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        $url = $this->locator->authorizationQueryToken($request->query->all());

        return new RedirectResponse($url['redirectUrl']);
    }

}
