<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Handler\ApplicationHandler;
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
     * @Route("/applications/{key}/users/{user}/uninstall", methods={"POST"})
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
     * @Route("/applications/{key}/users/{user}/settings", methods={"POST"})
     *
     * @param string  $key
     * @param string  $user
     * @param Request $request
     *
     * @return Response
     */
    public function updateApplicationSettingsAction(string $key, string $user, Request $request): Response
    {
        try {
            $settings = json_decode($request->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);

            $data = $this->applicationHandler->updateApplicationSettings($key, $user, $settings);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}/password", methods={"POST"})
     *
     * @param string  $key
     * @param string  $user
     * @param Request $request
     *
     * @return Response
     */
    public function saveApplicationPasswordAction(string $key, string $user, Request $request): Response
    {
        try {
            $password = $request->getContent();

            $data = $this->applicationHandler->updateApplicationPassword($key, $user, $password);

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }

    }

    /**
     * @Route("/applications/{key}/users/{user}/authorize", methods={"POST"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function authorizeApplicationAction(string $key, string $user): Response
    {
        try {
            $this->applicationHandler->authorizeApplication($key, $user);

            return $this->getResponse('Application is authorize');
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

}