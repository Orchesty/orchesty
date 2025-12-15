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
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class SettingsController
 *
 * @package Hanaboso\Applinth\Controller
 */
#[Route('/settings')]
final class SettingsController extends AbstractController
{

    // phpcs:disable SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing.IncorrectLinesCountBetweenAttributeAndTarget

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
     * @param string $sdk
     *
     * @return Response
     * @throws Throwable
     */
    #[Route('', methods: ['GET'])]
    public function getApplicationDetail(#[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->getAppDetail(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
                $sdk,
            ),
        );
    }

    /**
     * @param Request $request
     * @param string  $sdk
     *
     * @return Response
     */
    #[Route('', methods: ['PUT'])]
    public function updateApplication(Request $request, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->updateApp(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
                $sdk,
                $request->request->all(),
            ),
        );
    }

    /**
     * @param string $sdk
     * @param string $redirectUrl
     *
     * @return Response
     */
    #[Route('/authorize', methods: ['GET'])]
    public function authorizeApplication(
        #[MapQueryParameter] string $sdk,
        #[MapQueryParameter('redirect_url')] string $redirectUrl,
    ): Response {
        try {
            //TODO: refactor after ServiceLocatorMS will be done
            $this->locator->authorize(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
                $sdk,
                $redirectUrl,
            );
        } catch (Exception $e) {
            return new JsonResponse(['Error' => $e->getMessage()], 500);
        }

        return new JsonResponse([]);
    }

    /**
     * @param Request $request
     * @param string  $sdk
     *
     * @return Response
     */
    #[Route('/set-password', methods: ['PUT'])]
    public function setPassword(Request $request, #[MapQueryParameter] string $sdk): Response
    {
        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse(
            $this->locator->updateAppPassword(
                $this->authenticator->getRootKey(),
                $this->authenticator->getAuthUser(),
                $sdk,
                $request->request->all(),
            ),
        );
    }

}
