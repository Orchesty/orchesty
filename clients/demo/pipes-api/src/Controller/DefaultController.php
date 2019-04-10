<?php declare(strict_types=1);

namespace Demo\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * @package Demo\Controller
 */
class DefaultController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @Route("/", name="homepage")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->getResponse(['status' => 'ok']);
    }

}
