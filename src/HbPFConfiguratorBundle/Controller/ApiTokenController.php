<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\ApiTokenHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiTokenController
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller
 */
final class ApiTokenController extends AbstractController
{

    use ControllerTrait;

    /**
     * ApiTokenController constructor.
     *
     * @param ApiTokenHandler $handler
     */
    public function __construct(private readonly ApiTokenHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @Route("/apiTokens}", methods={"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws MongoDBException
     * @throws GridException
     */
    public function getApiTokensAction(Request $request): Response
    {
        $query = Json::decode($request->query->get('filter', '{}'));
        $dto   = new GridRequestDto($query);

        $dto->setAdditionalFilters(
            [
                [
                    [
                        GridFilterAbstract::COLUMN   => 'user',
                        GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                        GridFilterAbstract::VALUE    => [ApplicationController::SYSTEM_USER],
                    ],
                ],
            ],
        );

        $data = $this->handler->getAllBy($dto);

        return $this->getResponse($data);
    }

    /**
     * @Route("/apiTokens", methods={"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function createAction(Request $request): Response
    {
        try {
            return $this->getResponse(
                $this->handler->create($request->request->all(), ApplicationController::SYSTEM_USER),
            );
        } catch (MongoDBException $e) {
            return $this->getErrorResponse($e, 400);
        }
    }

    /**
     * @Route("/apiTokens/{id}", methods={"DELETE", "OPTIONS"}, requirements={"id": "\w+"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->delete($id));
        } catch (DocumentNotFoundException | MongoDBException $e) {
            return $this->getErrorResponse($e, 404);
        }
    }

}
