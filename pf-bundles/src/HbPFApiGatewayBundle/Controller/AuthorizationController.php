<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
class AuthorizationController extends FOSRestController
{

    /**
     * @Route("/authorizations/{authorizationId}/settings")
     * @Method({"GET", "OPTIONS"})
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
     * @Route("/authorizations/{authorizationId}/save_settings")
     * @Method({"PUT", "OPTIONS"})
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
     * @Route("/authorizations/{authorizationId}/authorize")
     * @Method({"POST", "OPTIONS"})
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
     * @Route("/authorizations/{authorizationId}/save_token")
     * @Method({"POST", "OPTIONS"})
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
     * @Route("/authorizations")
     * @Method({"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getAuthorizationsAction(): Response
    {
        return $this->forward('HbPFAuthorizationBundle:Authorization:getAuthorizations');
    }

}
