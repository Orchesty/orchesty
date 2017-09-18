<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class UserController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.user")
 */
class UserController extends FOSRestController
{

    /**
     *
     * @Route("/user/login")
     * @Method({"POST", "OPTIONS"})
     *
     * @return JsonResponse
     */
    public function loginAction(): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:login');

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     *
     * @Route("/user/logout")
     * @Method({"POST", "OPTIONS"})
     *
     * @return JsonResponse
     */
    public function logoutAction(): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:logout');

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     *
     * @Route("/user/register")
     * @Method({"POST", "OPTIONS"})
     *
     * @return JsonResponse
     */
    public function registerAction(): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:register');

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     *
     * @Route("/user/{token}/activate", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    public function activateAction(string $token): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:activate', ['token' => $token]);

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     *
     * @Route("/user/{token}/set_password", requirements={"token": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    public function setPasswordAction(string $token): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:setPassword', ['token' => $token]);

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     * @Route("/user/change_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @return JsonResponse
     */
    public function changePasswordAction(): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:changePassword');

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     *
     * @Route("/user/reset_password")
     * @Method({"POST", "OPTIONS"})
     *
     * @return JsonResponse
     */
    public function resetPasswordAction(): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:resetPassword');

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

    /**
     *
     * @Route("/user/{id}/delete")
     * @Method({"DELETE", "OPTIONS"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function deleteAction(string $id): JsonResponse
    {
        $data = $this->forward('HbPFUserBundle:User:delete', ['id' => $id]);

        return new JsonResponse(json_decode($data->getContent()), $data->getStatusCode(), $data->headers->all());
    }

}