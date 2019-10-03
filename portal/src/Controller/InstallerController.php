<?php declare(strict_types=1);

namespace Hanaboso\Portal\Controller;

use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use Hanaboso\Portal\Handler\InstallerHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class InstallerController
 *
 * @package Hanaboso\Portal\Controller
 */
class InstallerController extends AbstractFOSRestController
{

    use ControllerTrait;

    /**
     * @var InstallerHandler
     */
    private $installerHandler;

    /**
     * InstallerController constructor.
     *
     * @param InstallerHandler $installerHandler
     */
    public function __construct(InstallerHandler $installerHandler)
    {
        $this->installerHandler = $installerHandler;
    }

    /**
     * @Route("/installer", methods={"POST"})
     *
     * @return Response
     */
    public function installerAction(Request $request): Response
    {
        try {
            $data = $this->installerHandler->getInstaller($request->request->all());

            return $this->getResponse($data);
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

}