<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
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
     * @var ApplicationHandler
     */
    private ApplicationHandler $applicationHandler;

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
            $redirectUrl = $request->query->get('redirect_url', NULL);
            if (!$redirectUrl) {
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

            return $this->getResponse(['redirectUrl' => $url[ApplicationInterface::REDIRECT_URL]]);
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

            return $this->getResponse(['redirectUrl' => $url[ApplicationInterface::REDIRECT_URL]]);
        } catch (ApplicationInstallException $e) {
            return $this->getErrorResponse($e, 404, ControllerUtils::NOT_FOUND);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
