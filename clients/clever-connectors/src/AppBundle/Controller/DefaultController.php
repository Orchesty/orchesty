<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 *
 * @package CleverConnectors\AppBundle\Controller
 */
class DefaultController extends Controller
{

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $response = new Response($this->renderView('::default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
        ]), 200);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

}
