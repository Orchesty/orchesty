<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\AuditLogHandler;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use InvalidArgumentException;
use JsonException;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class AuditLogController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class AuditLogController
{

    use ControllerTrait;

    /**
     * AuditLogController constructor.
     *
     * @param AuditLogHandler $handler
     */
    public function __construct(private readonly AuditLogHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/audit-logs', methods: ['GET'], priority: 10)]
    public function listAction(Request $request): Response
    {
        try {
            $page  = max(1, $request->query->getInt('page', 1));
            $limit = max(1, min(100, $request->query->getInt('limit', 20)));
            $sort  = $request->query->getString('sort', 'timestamp');
            $order = $request->query->getString('order', 'desc');

            $search   = NULL;
            $action   = NULL;
            $resource = NULL;
            $from     = NULL;
            $to       = NULL;

            $filterRaw = $request->query->get('filter');
            if (is_string($filterRaw) && $filterRaw !== '') {
                try {
                    $filter   = Json::decode($filterRaw);
                    $search   = $filter['search'] ?? NULL;
                    $action   = $filter['action'] ?? NULL;
                    $resource = $filter['resource'] ?? NULL;
                    $from     = $filter['from'] ?? NULL;
                    $to       = $filter['to'] ?? NULL;
                } catch (JsonException) {
                }
            }

            return $this->getResponse(
                $this->handler->getAuditLogs($search, $action, $resource, $from, $to, $sort, $order, $page, $limit),
            );
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/audit-logs/{id}', methods: ['GET'], requirements: ['id' => '[a-f0-9]{24}'], priority: 10)]
    public function detailAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->getAuditLog($id));
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

}
