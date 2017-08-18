<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler\AuthorizationHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 */
class AuthorizationController extends FOSRestController
{

    /**
     * @var AuthorizationHandler
     */
    private $handler;

    /**
     * AuthorizationController constructor.
     *
     * @param AuthorizationHandler $handler
     */
    function __construct(AuthorizationHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/api/authorizations/{authorizationId}/authorize", defaults={}, requirements={"authorizationId": "\w+"})
     * @Method('POST')
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function authorization(Request $request, string $authorizationId): Response
    {
        $this->handler->authorize($authorizationId);

        return $this->handleView($this->redirectView($request->request->get('redirect_url')));
    }

    /**
     * @Route("/api/authorizations/{authorizationId}/save_token", defaults={}, requirements={"authorizationId": "\w+"})
     * @Method('POST')
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function saveToken(Request $request, string $authorizationId): Response
    {
        $this->handler->saveToken($request->request->all(), $authorizationId);

        return $this->handleView($this->redirectView('http://frontendURL.com'));
    }

    /**
     * @Route("/api/authorization/info")
     * @Method('GET')
     *
     * @return Response
     */
    public function getAuthorizationsInfo(): Response
    {
        $data = $this->handler->getAuthInfo();

        return $this->handleView($this->view($data));
    }

}
