<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Exception;
use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SettingsController
 *
 * @package Hanaboso\Applinth\Controller
 *
 * @Route("/settings")
 */
final class SettingsController extends AbstractController
{

    use ControllerTrait;

    /**
     * SettingsController constructor.
     *
     * @param EndUserAuthenticator $authenticator
     * @param ServiceLocator       $locator
     */
    public function __construct(
        private readonly EndUserAuthenticator $authenticator,
        private readonly ServiceLocator $locator,
    )
    {
    }

    /**
     * @Route("/", methods={"GET"})
     *
     * @return Response
     */
    public function getApplicationDetail(): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->getAppDetail(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
            ),
        );
    }

    /**
     * @Route("/", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateApplication(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->updateApp(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
                $request->request->all(),
            ),
        );
    }

    /**
     * @Route("/authorize", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function authorizeApplication(Request $request): Response
    {
        try {
            //TODO: refactor after ServiceLocatorMS will be done
            $this->locator->authorize(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
                (string) $request->query->get('redirect_url'),
            );
        } catch (Exception $e) {
            return new JsonResponse(['Error' => $e->getMessage()], 500);
        }

        return new JsonResponse([]);
    }

    /**
     * @Route("/set-password", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setPassword(Request $request): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->updateAppPassword(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
                $request->request->all(),
            ),
        );
    }

}
