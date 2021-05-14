<?php declare(strict_types=1);

namespace Hanaboso\Portal\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PortalController
 *
 * @package Hanaboso\Portal\Controller
 */
final class PortalController
{

    use ControllerTrait;

    /**
     * @Route("/", methods={"GET"})
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->getResponse(
            [
                'name'    => 'portal',
                'version' => '1.0.0',
                'status'  => 'OK',
            ],
        );
    }

}
