<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApplicationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class ApplicationController extends AbstractFOSRestController
{

    /**
     * @Route("/applications", methods={"GET"})
     *
     * @return Response
     */
    public function listOfApplicationsAction(): Response
    {
        return $this->forward('HbPFAppStoreBundle:Application:listOfApplications');
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
        return $this->forward('HbPFAppStoreBundle:Application:getApplication', [
            'key' => $key,
        ]);
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
        return $this->forward('HbPFAppStoreBundle:Application:getUsersApplication', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"GET"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function getApplicationDetailAction(string $key, string $user): Response
    {
        return $this->forward('HbPFAppStoreBundle:Application:getApplicationDetail', [
            'key'  => $key,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"POST"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function installApplicationAction(string $key, string $user): Response
    {
        return $this->forward('HbPFAppStoreBundle:Application:installApplication', [
            'key'  => $key,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $key
     * @param string  $user
     *
     * @return Response
     */
    public function updateApplicationSettingsAction(Request $request, string $key, string $user): Response
    {
        return $this->forward('HbPFAppStoreBundle:Application:updateApplicationSettings', [
            'request' => $request,
            'key'     => $key,
            'user'    => $user,
        ]);
    }

    /**
     * @Route("/applications/{key}/users/{user}", methods={"DELETE"})
     *
     * @param string $key
     * @param string $user
     *
     * @return Response
     */
    public function uninstallApplicationAction(string $key, string $user): Response
    {
        return $this->forward('HbPFAppStoreBundle:Application:uninstallApplication', [
            'key'  => $key,
            'user' => $user,
        ]);
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
        return $this->forward('HbPFAppStoreBundle:Application:saveApplicationPassword', [
            'request' => $request,
            'key'     => $key,
            'user'    => $user,
        ]);
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
        return $this->forward('HbPFApplicationBundle:Application:authorizeApplication', [
            'request' => $request,
            'key'     => $key,
            'user'    => $user,
        ]);
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
        return $this->forward('HbPFApplicationBundle:Application:setAuthorizationToken', [
            'request' => $request,
            'key'     => $key,
            'user'    => $user,
        ]);
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
        return $this->forward('HbPFApplicationBundle:Application:setAuthorizationTokenQuery', [
            'request' => $request,
        ]);
    }

}
