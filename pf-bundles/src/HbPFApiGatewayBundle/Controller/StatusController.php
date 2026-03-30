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
class StatusController extends AbstractController
{

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
            'status'    => 'ok',
            'cloudMode' => $this->orchesryCloudUrl !== '',
        ];

        if ($this->orchesryCloudUrl !== '') {
            $frontendUrl = $this->orchesryCloudFrontendUrl !== ''
                ? $this->orchesryCloudFrontendUrl
                : $this->orchesryCloudUrl;
            $data['cloudUrl'] = rtrim($frontendUrl, '/');
        }

        return new JsonResponse($data);
    }

}
