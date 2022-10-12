<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller;

use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class ApplicationController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller
 */
final class ApplicationController
{

    use ControllerTrait;

    /**
     * ApplicationController constructor.
     *
     * @param ApplicationHandler $applicationHandler
     */
    public function __construct(private ApplicationHandler $applicationHandler)
    {
    }

    /**
     * @Route("/applications", methods={"GET"})
     * @Route("/applications/", methods={"GET"})
     *
     * @return Response
     */
    public function listOfApplicationsAction(): Response
    {
        try {
            return $this->getResponse($this->applicationHandler->getApplications());
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
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
            return $this->getResponse($this->applicationHandler->getApplicationByKey($key));
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/applications/{key}/sync/list", methods={"GET"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function getSynchronousActionsAction(string $key): Response
    {
        try {
            return $this->getResponse($this->applicationHandler->getSynchronousActions($key));
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
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
        try {
            $res = $this->applicationHandler->runSynchronousAction($key, $method, $request);
            $res = is_array($res) ? $res : [$res];

            return $this->getResponse($res);
        } catch (ApplicationInstallException $e) {
            if ($e->getCode() === ApplicationInstallException::METHOD_NOT_FOUND) {
                return $this->getErrorResponse($e);
            }

            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/applications/{key}/users/{user}/authorize", methods={"GET"})
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
            /** @var string $redirectUrl */
            $redirectUrl = $request->query->get('redirect_url', '');
            if (empty($redirectUrl)) {
                throw new InvalidArgumentException('Missing "redirect_url" query parameter.');
            }

            $url = $this->applicationHandler->authorizeApplication($key, $user, $redirectUrl);

            return $this->getResponse(['authorizeUrl' => $url]);
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
            $url = $this->applicationHandler->saveAuthToken($key, $user, $request->query->all());

            return $this->getResponse(['redirectUrl' => $url]);
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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

            return $this->getResponse(['redirectUrl' => $url]);
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
            return $this->getResponse($this->applicationHandler->getApplicationsByUser($user));
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
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
            return $this->getResponse($this->applicationHandler->getApplicationByKeyAndUser($key, $user));
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
            return $this->getResponse($this->applicationHandler->installApplication($key, $user));
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
            return $this->getResponse($this->applicationHandler->uninstallApplication($key, $user));
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/applications/:{key}/users/{user}/changeState'", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function changeStateOfApplication(Request $request, string $key, string $user): Response
    {
        try {
            return $this->getResponse($this->applicationHandler->changeStateOfApplication(
                $key,
                $user,
                $request->get('enabled'),
            ));
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
            return $this->getResponse(
                $this->applicationHandler->updateApplicationSettings($key, $user, $request->request->all()),
            );
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
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
            return $this->getResponse(
                $this->applicationHandler->updateApplicationPassword(
                    $key,
                    $user,
                    $request->request->all(),
                ),
            );
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
