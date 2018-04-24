<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="cc.default.controller")
 */
class DefaultController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @Route("/")
     * @Method("GET")
     *
     * @return Response
     */
    public function defaultAction(): Response
    {
        return $this->getResponse('OK', 200);
    }

}
