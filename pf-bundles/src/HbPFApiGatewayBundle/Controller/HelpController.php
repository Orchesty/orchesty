<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class HelpController
 *
 * Serves pre-built help documentation (manifest, pages, search index).
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class HelpController extends AbstractController
{

    /**
     * HelpController constructor.
     *
     * @param string $helpDir
     */
    public function __construct(
        private readonly string $helpDir = '/srv/app/help',
    )
    {
    }

    /**
     * @return Response
     */
    #[Route('/help/manifest', methods: ['GET'])]
    public function manifestAction(): Response
    {
        return $this->serveJsonFile('manifest.json');
    }

    /**
     * @return Response
     */
    #[Route('/help/search-index', methods: ['GET'])]
    public function searchIndexAction(): Response
    {
        return $this->serveJsonFile('search-index.json');
    }

    /**
     * @param string $slug
     *
     * @return Response
     */
    #[Route('/help/page/{slug}', methods: ['GET'], requirements: ['slug' => '.+'])]
    public function pageAction(string $slug): Response
    {
        if (str_contains($slug, '..') || str_starts_with($slug, '/')) {
            return new JsonResponse(['error' => 'Invalid slug.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->serveJsonFile(sprintf('pages/%s.json', $slug));
    }

    /**
     * @param string $relativePath
     *
     * @return Response
     */
    private function serveJsonFile(string $relativePath): Response
    {
        $file = sprintf('%s/%s', rtrim($this->helpDir, '/'), $relativePath);

        if (!file_exists($file)) {
            return new JsonResponse(['error' => 'Not found.'], Response::HTTP_NOT_FOUND);
        }

        $content = file_get_contents($file);

        if ($content === FALSE) {
            return new JsonResponse(['error' => 'Failed to read file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR));
    }

}
