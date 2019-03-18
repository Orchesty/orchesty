<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class AuthorizationController extends AbstractFOSRestController
{

    /**
     * @Route("/authorizations/{authorizationId}/settings", methods={"GET", "OPTIONS"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function getSettingsAction(string $authorizationId): Response
    {
        return $this->forward('HbPFAuthorizationBundle:Authorization:getSettings',
            ['authorizationId' => $authorizationId]);
    }

    /**
     * @Route("/authorizations/{authorizationId}/save_settings", methods={"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function saveSettingsAction(Request $request, string $authorizationId): Response
    {
        return $this->forward('HbPFAuthorizationBundle:Authorization:saveSettings',
            ['authorizationId' => $authorizationId, 'query' => $request->query]);
    }

    /**
     * @Route("/authorizations/{authorizationId}/authorize", methods={"POST", "OPTIONS"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function authorizationAction(string $authorizationId): Response
    {
        return $this->forward('HbPFAuthorizationBundle:Authorization:authorization',
            ['authorizationId' => $authorizationId]);
    }

    /**
     * @Route("/authorizations/{authorizationId}/save_token", methods={"POST", "OPTIONS"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function saveTokenAction(string $authorizationId): Response
    {
        return $this->forward('HbPFAuthorizationBundle:Authorization:saveToken',
            ['authorizationId' => $authorizationId]);
    }

    /**
     * @Route("/authorizations", methods={"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getAuthorizationsAction(): Response
    {
        return $this->forward('HbPFAuthorizationBundle:Authorization:getAuthorizations');
    }

}
