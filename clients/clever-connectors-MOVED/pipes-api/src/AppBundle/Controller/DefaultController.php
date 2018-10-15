<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * @package CleverConnectors\AppBundle\Controller
 */
class DefaultController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @Route("/", methods={"GET"})
     *
     * @return Response
     */
    public function defaultAction(): Response
    {
        return $this->getResponse('OK', 200);
    }

}
