<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\PipesFramework\ApiGateway\Exception\LicenseException;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Utils\JWTParser;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
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

    private const APPLICATIONS = 'applications';

    /**
     * @var ObjectRepository<ApplicationInstall>&ApplicationInstallRepository
     */
    protected $repository;

    /**
     * ApplicationController constructor.
     *
     * @param ServiceLocator  $locator
     * @param DocumentManager $dm
     */
    public function __construct(private ServiceLocator $locator, private DocumentManager $dm)
    {
        $this->repository = $this->dm->getRepository(ApplicationInstall::class);
    }

    /**
     * @Route("/applications", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function listOfApplicationsAction(): Response
    {
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getApps());
    }

    /**
     * @Route("/applications/{key}", methods={"GET", "OPTIONS"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function getApplicationAction(string $key): Response
    {
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getApp($key));
    }

    /**
     * @Route("/applications/users/{user}", methods={"GET", "OPTIONS"})
     *
     * @param string $user
     *
     * @return Response
     */
    public function getUsersApplicationAction(string $user): Response
    {
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getUserApps($user));
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"GET", "OPTIONS"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function getApplicationDetailAction(string $key, string $user): Response
    {
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->getAppDetail($key, $user));
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"POST", "OPTIONS"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function installApplicationAction(string $key, string $user): Response
    {
        try {
            $this->verifyLicense(TRUE);
        } catch (LicenseException $e) {
            return $this->getErrorResponse($e, 400);
        }

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->installApp($key, $user));
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function updateApplicationSettingsAction(Request $request, string $key, string $user): Response
    {
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->updateApp($key, $user, $request->request->all()));
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"DELETE", "OPTIONS"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function uninstallApplicationAction(string $key, string $user): Response
    {
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->uninstallApp($key, $user));
    }

    /**
     * @Route("/applications/{key}/users/{user}/password", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function saveApplicationPasswordAction(Request $request, string $key, string $user): Response
    {
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        return new JsonResponse($this->locator->updateAppPassword($key, $user, $request->request->all()));
    }

    /**
     * @Route("/applications/{key}/users/{user}/authorize", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function authorizeApplicationAction(Request $request, string $key, string $user): Response
    {
        $this->verifyLicense();

        try {
            //TODO: refactor after ServiceLocatorMS will be done
            $this->locator->authorize($key, $user, (string) $request->query->get('redirect_url'));
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
        $this->verifyLicense();

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
        $this->verifyLicense();

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
        $this->verifyLicense();

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
        $this->verifyLicense();

        //TODO: refactor after ServiceLocatorMS will be done
        $url = $this->locator->authorizationQueryToken($request->query->all());

        return new RedirectResponse($url['redirectUrl']);
    }

    /**
     * @Route("/applications/statistics/application/{key}", methods={"GET", "OPTIONS"})
     *
     * @param Request     $request
     * @param string|null $key
     *
     * @return Response
     */
    public function applicationStatisticsAction(Request $request, ?string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::applicationMetricsAction',
            ['request' => $request, 'key' => $key],
        );
    }

    /**
     * @Route("/applications/statistics/user/{user}", methods={"GET", "OPTIONS"})
     *
     * @param Request     $request
     * @param string|null $user
     *
     * @return Response
     */
    public function userStatisticsAction(Request $request, ?string $user): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFMetricsBundle\Controller\MetricsController::userMetricsAction',
            ['request' => $request, 'user' => $user],
        );
    }

    /**
     * @throws LicenseException
     * @throws MongoDBException
     */
    private function verifyLicense(bool $lastInstall = FALSE): void
    {
        $offset = $lastInstall ? 1 : 0;
        $apps   = $this->repository->getInstalledApplicationsCount();
        if (JWTParser::verifyAndReturn()[self::APPLICATIONS] < $apps + $offset) {
            throw new LicenseException(
                'Your license is not valid or application limit is exceeded',
                LicenseException::LICENSE_NOT_VALID_OR_APPS_EXCEED,
            );
        }
    }

}
