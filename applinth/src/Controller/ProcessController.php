<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyProgressHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class ProcessController
 *
 * @package Hanaboso\Applinth\Controller
 */
#[Route('/process')]
final class ProcessController extends AbstractController
{

    use ControllerTrait;

    private const string USER = 'user';

    /**
     * ProcessController constructor.
     *
     * @param EndUserAuthenticator    $authenticator
     * @param TopologyProgressHandler $topologyProgressHandler
     */
    public function __construct(
        private readonly EndUserAuthenticator $authenticator,
        private readonly TopologyProgressHandler $topologyProgressHandler,
    )
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/overview', methods: ['GET'])]
    public function getOverview(Request $request): Response
    {
        try {
            return $this->getResponse(
                $this->topologyProgressHandler->getProgress($this->createDtoWithUserFilter($request)),
            );
        } catch (Throwable $t) {
            return $this->getErrorResponse($t);
        }
    }

    /**
     * @param Request $request
     *
     * @return GridRequestDto
     * @throws GridException
     */
    private function createDtoWithUserFilter(Request $request): GridRequestDto
    {
        $gridRequestDto = new GridRequestDto(Json::decode($request->query->get('filter', '{}')));

        return $gridRequestDto->setAdditionalFilters(
            [
                [
                    [
                        GridFilterAbstract::COLUMN   => self::USER,
                        GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                        GridFilterAbstract::VALUE    => [$this->authenticator->getAuthUser()],
                    ],
                ],
            ],
        );
    }

}
