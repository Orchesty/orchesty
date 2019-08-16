<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFAuthorizationBundle\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\HbPFAuthorizationBundle\Handler\ApplicationHandler;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class ApplicationController
 *
 * @package Hanaboso\PipesPhpSdk\HbPFAuthorizationBundle\Controller
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
