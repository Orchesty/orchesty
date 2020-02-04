<?php declare(strict_types=1);

namespace Hanaboso\Portal\Controller;

use Exception;
use Hanaboso\Portal\Handler\InstallerHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class InstallerController
 *
 * @package Hanaboso\Portal\Controller
 */
class InstallerController
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
        $this->logger           = new NullLogger();
    }

    /**
     * @Route("/installer", methods={"POST"})
     * @param Request $request
     *
     * @return Response
     */
    public function installerAction(Request $request): Response
    {
        try {
            $data = $this->installerHandler->getInstaller($request->request->all());

            $response = new Response($data);

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'docker-compose.yml'
            );

            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        } catch (Exception|Throwable $e) {
            return $this->getErrorResponse($e, 500);
        }
    }

}
