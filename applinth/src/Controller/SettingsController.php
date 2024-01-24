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
use Throwable;

/**
 * Class SettingsController
 *
 * @package Hanaboso\Applinth\Controller
 */
#[Route('/settings')]
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
     * @return Response
     * @throws Throwable
     */
    #[Route('', methods: ['GET'])]
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
     * @param Request $request
     *
     * @return Response
     */
    #[Route('', methods: ['PUT'])]
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
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/authorize', methods: ['GET'])]
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
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/set-password', methods: ['PUT'])]
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
