<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApplicationController
 *
 * @package Hanaboso\Applinth\Controller
 *
 * @Route("/application")
 */
final class ApplicationController extends AbstractController
{

    use ControllerTrait;

    /**
     * ApplicationController constructor.
     *
     * @param EndUserAuthenticator $authenticator
     */
    public function __construct(private readonly EndUserAuthenticator $authenticator)
    {
    }

    /**
     * @Route("/installed", methods={"GET"})
     *
     * @return Response
     */
    public function getInstalledApplications(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getUsersApplicationAction',
            ['user' => $this->authenticator->getAuthUser()],
            ['exclude' => $this->authenticator->getRootKey()],
        );
    }

    /**
     * @Route("/available", methods={"GET"})
     *
     * @return Response
     */
    public function getAvailableApplications(): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::listOfApplicationsAction',
            [],
            ['exclude' => $this->authenticator->getRootKey()],
        );
    }

    /**
     * @Route("/{key}/preview", methods={"GET"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function getApplicationDetail(Request $request, string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationAction',
            ['request' => $request, 'key' => $key],
        );
    }

    /**
     * @Route("/{key}", methods={"GET"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function getInstalledApplicationDetail(Request $request, string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationDetailAction',
            ['request' => $request, 'key' => $key, 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/{key}/authorize", methods={"GET"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function authorizeApplication(Request $request, string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::authorizeApplicationAction',
            ['request' => $request, 'key' => $key, 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/{key}", methods={"POST"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function installApplication(string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::installApplicationAction',
            ['key' => $key, 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/{key}", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function updateApplication(Request $request, string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::updateApplicationSettingsAction',
            ['request' => $request, 'key' => $key, 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/{key}/changeState", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function changeStateApplication(Request $request, string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::changeStateApplicationAction',
            ['request' => $request, 'key' => $key, 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/{key}", methods={"DELETE"})
     *
     * @param string $key
     *
     * @return Response
     */
    public function uninstallApplication(string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::uninstallApplicationAction',
            ['key' => $key, 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/{key}/set-password", methods={"PUT"})
     *
     * @param Request $request
     * @param string  $key
     *
     * @return Response
     */
    public function setPassword(Request $request, string $key): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::saveApplicationPasswordAction',
            ['request' => $request, 'key' => $key, 'user' => $this->authenticator->getAuthUser()],
        );
    }

}
