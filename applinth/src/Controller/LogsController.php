<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFLogsBundle\Handler\LogsHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogsController
 *
 * @package Hanaboso\Applinth\Controller
 */
final class LogsController extends AbstractController
{

    use ControllerTrait;

    private const USER = 'user_id';

    /**
     * LogsController constructor.
     *
     * @param EndUserAuthenticator $authenticator
     * @param LogsHandler          $logsHandler
     */
    public function __construct(
        private readonly EndUserAuthenticator $authenticator,
        private readonly LogsHandler $logsHandler,
    )
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws GridException
     */
    #[Route('/logs', methods: ['GET'])]
    public function getDataForTableAction(Request $request): Response
    {
        $dto = new GridRequestDto(Json::decode($request->query->get('filter', '{}')));

        $dto->setAdditionalFilters([
            [
                [
                    GridFilterAbstract::COLUMN   => self::USER,
                    GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                    GridFilterAbstract::VALUE    => [$this->authenticator->getAuthUser()],
                ],
            ],
        ]);

        return new JsonResponse($this->logsHandler->getData($dto, 0));
    }

}
