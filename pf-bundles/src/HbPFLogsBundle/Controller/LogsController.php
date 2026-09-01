<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Controller;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\HbPFLogsBundle\Handler\LogsHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class LogsController
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\Controller
 */
final class LogsController
{

    use ControllerTrait;

    /**
     * LogsController constructor.
     *
     * @param LogsHandler $handler
     */
    public function __construct(private readonly LogsHandler $handler)
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/logs-old', methods: ['GET', 'OPTIONS'])]
    public function getDataForTableAction(Request $request): Response
    {
        $filter     = Json::decode($request->query->get('filter', '{}'));
        $newFilter  = [];
        $timeMargin = 0;

        foreach ($filter['filter'] ?? [] as $and) {
            $newAnd = [];
            foreach ($and as $field) {
                if ($field['column'] !== 'time_margin') {
                    $newAnd[] = $field;
                } else {
                    $timeMargin = $field['value'];
                }
            }
            $newFilter[] = $newAnd;
        }
        $filter['filter'] = $newFilter;

        $dto = new GridRequestDto($filter);

        return new JsonResponse($this->handler->getData($dto, $timeMargin));
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    #[Route('/logs', methods: [Request::METHOD_GET])]
    public function getLogsAction(Request $request): Response
    {
        return $this->getResponse(
            $this->handler->getLogs(
                new GridRequestDto(Json::decode($request->query->get('filter', '{}'))),
            ),
        );
    }

}
