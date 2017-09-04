<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 *
 * @Route(service="hbpf.commons.controller.api")
 */
class ApiController extends FOSRestController
{

    /**
     * ApiController constructor.
     */
    public function __construct()
    {

    }

    /**
     * @Route("/api/nodes/{nodeId}/process", defaults={}, requirements={"nodeId": "\w+"})
     * @ParamConverter(
     *     "message",
     *     class="Hanaboso\PipesFramework\Commons\Message\DefaultMessage",
     *     converter="fos_rest.request_body"
     * )
     *
     * @return JsonResponse
     */
    public function nodeAction(): JsonResponse
    {
        return new JsonResponse();
    }

}
