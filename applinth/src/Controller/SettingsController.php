<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SettingsController
 *
 * @package Hanaboso\Applinth\Controller
 *
 * @Route("/settings")
 */
final class SettingsController extends AbstractController
{

    use ControllerTrait;

    /**
     * SettingsController constructor.
     *
     * @param EndUserAuthenticator $authenticator
     */
    public function __construct(private readonly EndUserAuthenticator $authenticator)
    {
    }

    /**
     * @Route("/", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getApplicationDetail(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::getApplicationDetailAction',
            ['request' => $request, 'key' => $this->authenticator->getRootKey(), 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateApplication(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::updateApplicationSettingsAction',
            ['request' => $request, 'key' => $this->authenticator->getRootKey(), 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/authorize", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function authorizeApplication(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::authorizeApplicationAction',
            ['request' => $request, 'key' => $this->authenticator->getRootKey(), 'user' => $this->authenticator->getAuthUser()],
        );
    }

    /**
     * @Route("/set-password", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function setPassword(Request $request): Response
    {
        return $this->forward(
            'Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController::saveApplicationPasswordAction',
            ['request' => $request, 'key' => $this->authenticator->getRootKey(), 'user' => $this->authenticator->getAuthUser()],
        );
    }

}
