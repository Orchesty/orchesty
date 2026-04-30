<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\TraceReportHandler;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\Traits\ControllerTrait;
use InvalidArgumentException;
use JsonException;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class TraceReportController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class TraceReportController
{

    use ControllerTrait;

    /**
     * TraceReportController constructor.
     *
     * @param TraceReportHandler $handler
     */
    public function __construct(private readonly TraceReportHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/trace-reports', methods: ['GET'])]
    public function listAction(Request $request): Response
    {
        try {
            $page  = max(1, $request->query->getInt('page', 1));
            $limit = max(1, min(200, $request->query->getInt('limit', 50)));

            return $this->getResponse($this->handler->list($page, $limit));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/trace-reports', methods: ['POST'])]
    public function createAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->create($this->decodeBody($request)));
        } catch (MongoDBException | PipesFrameworkException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    #[Route('/trace-reports/{id}', methods: ['PATCH'], requirements: ['id' => '[a-f0-9]{24}'])]
    public function updateAction(Request $request, string $id): Response
    {
        try {
            return $this->getResponse($this->handler->update($id, $this->decodeBody($request)));
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (MongoDBException | PipesFrameworkException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/trace-reports/{id}', methods: ['DELETE'], requirements: ['id' => '[a-f0-9]{24}'])]
    public function deleteAction(string $id): Response
    {
        try {
            return $this->getResponse($this->handler->delete($id));
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return mixed[]
     */
    private function decodeBody(Request $request): array
    {
        $content = $request->getContent();
        if ($content === '') {
            return $request->request->all();
        }

        try {
            return Json::decode($content);
        } catch (JsonException) {
            return $request->request->all();
        }
    }

}
