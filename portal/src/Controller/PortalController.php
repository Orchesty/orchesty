<?php declare(strict_types=1);

namespace Hanaboso\Portal\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PortalController
 *
 * @package Hanaboso\Portal\Controller
 */
final class PortalController extends AbstractFOSRestController
{

    use ControllerTrait;

    //    /**
    //     * @var PortalsHandler
    //     */
    //    private $handler;
    //
    //    /**
    //     * PortalsController constructor.
    //     *
    //     * @param PortalsHandler $handler
    //     */
    //    public function __construct(PortalsHandler $handler)
    //    {
    //        $this->handler = $handler;
    //    }

    /**
     * @Rest\Route("/", methods={"GET"})
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->getResponse('portal');
    }

}
