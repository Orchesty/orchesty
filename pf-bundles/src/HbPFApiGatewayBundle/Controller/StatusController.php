<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class StatusController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class StatusController extends AbstractController
{

    /**
     * StatusController constructor.
     *
     * @param string $orchesryCloudUrl
     * @param string $orchesryCloudFrontendUrl
     */
    public function __construct(
        private readonly string $orchesryCloudUrl = '',
        private readonly string $orchesryCloudFrontendUrl = '',
    )
    {
    }

    /**
     * @return Response
     */
    #[Route('/status', methods: ['GET'])]
    public function getStatusAction(): Response
    {
        $data = [
            'cloudMode' => $this->orchesryCloudUrl !== '',
            'status'    => 'ok',
        ];

        if ($this->orchesryCloudUrl !== '') {
            $frontendUrl      = $this->orchesryCloudFrontendUrl !== ''
                ? $this->orchesryCloudFrontendUrl
                : $this->orchesryCloudUrl;
            $data['cloudUrl'] = rtrim($frontendUrl, '/');
        }

        return new JsonResponse($data);
    }

}
