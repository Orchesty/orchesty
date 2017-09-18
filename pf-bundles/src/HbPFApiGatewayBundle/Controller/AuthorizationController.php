<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.authorization")
 */
class AuthorizationController extends FOSRestController
{

    /**
     * @Route("/authorizations/{authorizationId}/authorize", defaults={}, requirements={"authorizationId": "\w+"})
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
     * @Route("/authorizations/{authorizationId}/save_token", defaults={}, requirements={"authorizationId": "\w+"})
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
     * @Route("/authorization/info")
     * @Method({"GET", "OPTIONS"})
     *
     * @return Response
     */
    public function getAuthorizationsInfoAction(): Response
    {
        return $this->forward('HbPFAuthorizationBundle:Authorization:getAuthorizationsInfo');
    }

}
