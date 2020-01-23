<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    /**
     * @Route("/applications", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function listOfApplicationsAction(): Response
    {
        return $this->forward('Hanaboso\HbPFAppStore\Controller\ApplicationController::listOfApplicationsAction');
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\ApplicationController::getApplicationAction',
            [
                'key' => $key,
            ]
        );
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\ApplicationController::getUsersApplicationAction',
            [
                'user' => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\ApplicationController::getApplicationDetailAction',
            [
                'key'  => $key,
                'user' => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\ApplicationController::installApplicationAction',
            [
                'key'  => $key,
                'user' => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\ApplicationController::updateApplicationSettingsAction',
            [
                'request' => $request,
                'key'     => $key,
                'user'    => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\ApplicationController::uninstallApplicationAction',
            [
                'key'  => $key,
                'user' => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\HbPFAppStore\Controller\ApplicationController::saveApplicationPasswordAction',
            [
                'request' => $request,
                'key'     => $key,
                'user'    => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::authorizeApplicationAction',
            [
                'request' => $request,
                'key'     => $key,
                'user'    => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenAction',
            [
                'request' => $request,
                'key'     => $key,
                'user'    => $user,
            ]
        );
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
        return $this->forward(
            'Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction',
            [
                'request' => $request,
            ]
        );
    }

}
