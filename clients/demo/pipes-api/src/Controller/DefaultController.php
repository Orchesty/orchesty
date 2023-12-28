<?php declare(strict_types=1);

namespace Demo\Controller;

use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * @package Demo\Controller
 */
final class DefaultController
{

    use ControllerTrait;

    /**
     * @return Response
     */
    #[Route('/', name: 'homepage')]
    public function indexAction(): Response
    {
        return $this->getResponse(['status' => 'ok']);
    }

}
