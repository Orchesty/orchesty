<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StatusController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class StatusController extends AbstractController
{

    /**
     * @Route("/status", methods={"GET"})
     *
     * @return Response
     */
    public function getStatusAction(): Response
    {
        return new JsonResponse(['status' => 'ok']);
    }

}
