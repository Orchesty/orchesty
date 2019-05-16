<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Handler\ApplicationHandler;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class ApplicationController
 *
 * @package Hanaboso\PipesFramework\HbPFApplicationBundle\Controller
 */
class ApplicationController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var ApplicationHandler
     */
    private $applicationHandler;

    /**
     * ApplicationController constructor.
     *
     * @param ApplicationHandler $applicationHandler
     */
    public function __construct(ApplicationHandler $applicationHandler)
    {
        $this->applicationHandler = $applicationHandler;
    }

    /**
     * @Route("/applications", methods={"GET"})
     *
     * @return Response
     */
    public function listOfApplicationsAction(): Response
    {
        try {
            $data = $this->applicationHandler->getApplications();

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}", methods={"GET"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function getApplicationAction(string $key): Response
    {
        try {
            $data = $this->applicationHandler->getApplicationByKey($key);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/users/{user}", methods={"GET"})
     *
     * @param string $user
     *
     * @return Response
     */
    public function getUsersApplicationAction(string $user): Response
    {
        try {
            $data = $this->applicationHandler->getApplicationsByUser($user);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}",  methods={"GET"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function getApplicationDetailAction(string $key, string $user): Response
    {
        try {
            $data = $this->applicationHandler->getApplicationByKeyAndUser($key, $user);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}/install",  methods={"POST"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function installApplicationAction(string $key, string $user): Response
    {
        try {
            $data = $this->applicationHandler->installApplication($key, $user);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}/uninstall", methods={"DELETE"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function uninstallApplicationAction(string $key, string $user): Response
    {
        try {
            $data = $this->applicationHandler->uninstallApplication($key, $user);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}/settings", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function updateApplicationSettingsAction(Request $request, string $key, string $user): Response
    {
        try {
            $data = $this->applicationHandler->updateApplicationSettings($key, $user, $request->request->all());

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}/password", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function saveApplicationPasswordAction(Request $request, string $key, string $user): Response
    {
        try {
            $data = $this->applicationHandler->updateApplicationPassword($key, $user, $request->request->all());

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}/authorize", methods={"POST"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function authorizeApplicationAction(Request $request, string $key, string $user): Response
    {
        try {
            $redirectUrl = $request->query->get('redirect_url', NULL);
            if (!$redirectUrl) {
                throw new InvalidArgumentException('Missing "redirect_url" query parameter.');
            }

            $this->applicationHandler->authorizeApplication($key, $user, $redirectUrl);

            return $this->getResponse([]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

    /**
     * @Route("/applications/{key}/users/{user}/authorize/token", methods={"GET"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function setAuthorizationTokenAction(Request $request, string $key, string $user): Response
    {
        try {
            $url = $this->applicationHandler->saveAuthToken($key, $user, $request->request->all());

            return new RedirectResponse($url[ApplicationInterface::REDIRECT_URL]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

    /**
     * @Route("/applications/authorize/token", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setAuthorizationTokenQueryAction(Request $request): Response
    {
        try {
            [$user, $key] = OAuth2Provider::stateDecode($request->get('state'));
            $url = $this->applicationHandler->saveAuthToken($key, $user, $request->query->all());

            return new RedirectResponse($url[ApplicationInterface::REDIRECT_URL]);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

}