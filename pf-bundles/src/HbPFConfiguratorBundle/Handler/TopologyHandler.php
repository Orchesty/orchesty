<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\NodeManager;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Model\TopologyTester;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\String\UriParams;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Validations\Validations;
use Throwable;

/**
 * Class TopologyHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class TopologyHandler
{

    private const STARTING_POINTS = 'startingPoints';
    private const BODY            = 'body';

    /**
     * @var ObjectRepository<Topology>&TopologyRepository
     */
    protected TopologyRepository $topologyRepository;

    /**
     * @var DocumentManager
     */
    protected DocumentManager $dm;

    /**
     * @var CurlManagerInterface
     */
    protected CurlManagerInterface $curlManager;

    /**
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator  $dml
     * @param TopologyManager         $topologyManager
     * @param NodeManager             $nodeManager
     * @param TopologyGeneratorBridge $generatorBridge
     * @param UserTaskHandler         $userTaskHandler
     * @param TopologyTester          $topologyTester
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        protected TopologyManager $topologyManager,
        protected NodeManager $nodeManager,
        protected TopologyGeneratorBridge $generatorBridge,
        protected UserTaskHandler $userTaskHandler,
        protected TopologyTester $topologyTester,
    )
    {
        /** @var DocumentManager $dm */
        $dm                       = $dml->getDm();
        $this->dm                 = $dm;
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
    }

    /**
     * @param string  $topologyId
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws CurlException
     */
    public function runTopology(string $topologyId, array $data): array
    {
        Validations::checkParams([self::STARTING_POINTS, self::BODY], $data);

        $topologiesStatus = [];

        foreach ($data[self::STARTING_POINTS] as $startingPointId) {
            $topologiesStatus[] = $this->topologyManager->runTopology($topologyId, $startingPointId, $data[self::BODY]);
        }

        return $topologiesStatus;
    }

    /**
     * @param string  $topologyName
     * @param string  $nodeName
     * @param mixed[] $data
     * @param string  $user
     *
     * @return mixed[]
     * @throws CurlException
     */
    public function runTopologyByName(
        string $topologyName,
        string $nodeName,
        array $data,
        string $user = ApplicationController::SYSTEM_USER,
    ): array
    {
        return $this->topologyManager->runTopology($topologyName, $nodeName, $data[self::BODY] ?? '[]', TRUE, $user);
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param null     $orderBy
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getTopologies(?int $limit = NULL, ?int $offset = NULL, $orderBy = NULL): array
    {
        $sort = UriParams::parseOrderBy($orderBy);
        /** @var Topology[] $topologies */
        $topologies = $this->topologyRepository->findBy(['deleted' => FALSE], $sort, $limit, $offset);

        $data = [
            'items' => [],
        ];
        foreach ($topologies as $topology) {
            $data['items'][] = $this->getTopologyData($topology);
        }

        $data['total']  = $this->topologyRepository->getTotalCount();
        $data['limit']  = $limit;
        $data['count']  = count($topologies);
        $data['offset'] = $offset;

        return $data;
    }

    /**
     * @return mixed[]
     * @throws CurlException
     * @throws CronException
     */
    public function getCronTopologies(): array
    {
        $cron = $this->topologyManager->getCronTopologies();

        return [
            'items'  => $cron,
            'paging' => [
                'itemsPerPage' => 50,
                'lastPage'     => 2,
                'nextPage'     => 2,
                'page'         => 1,
                'previousPage' => 1,
                'total'        => count($cron),
            ],
        ];
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws TopologyException
     * @throws MongoDBException
     */
    public function getTopology(string $id): array
    {
        $topology = $this->getTopologyById($id);

        return $this->getTopologyData($topology);
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     * @throws MongoDBException
     * @throws TopologyException
     */
    public function createTopology(array $data): array
    {
        ControllerUtils::checkParameters(['name'], $data);

        $topology = $this->topologyManager->createTopology($data);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws TopologyException
     * @throws MongoDBException
     * @throws CurlException
     */
    public function updateTopology(string $id, array $data): array
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->topologyManager->updateTopology($topology, $data);

        $this->generatorBridge->invalidateTopologyCache($topology->getName());

        return $this->getTopologyData($topology);
    }

    /**
     * @param string $id
     *
     * @return string
     * @throws TopologyException
     */
    public function getTopologySchema(string $id): string
    {
        return $this->getTopologyById($id)->getRawBpmn();
    }

    /**
     * @param string  $id
     * @param string  $content
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws CronException
     * @throws CurlException
     * @throws NodeException
     * @throws TopologyException
     * @throws MongoDBException
     */
    public function saveTopologySchema(string $id, string $content, array $data): array
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->topologyManager->saveTopologySchema($topology, $content, $data);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws TopologyException
     */
    public function checkTopologySchemaDifferences(string $id, array $data): array
    {
        $topology = $this->getTopologyById($id);

        return ['isDifferent' => !$this->topologyManager->checkTopologySchemaIsSame($topology, $data)];
    }

    /**
     * @param string $id
     *
     * @return ResponseDto
     * @throws TopologyException
     * @throws EnumException
     * @throws MongoDBException
     */
    public function publishTopology(string $id): ResponseDto
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->topologyManager->publishTopology($topology);
        $data     = $this->getTopologyData($topology);

        try {
            $generateResult = $this->generatorBridge->generateTopology($id);
            $runResult      = $this->generatorBridge->runTopology($id);
            $code           = 200;
            if ($generateResult->getStatusCode() !== 200 || $runResult->getStatusCode() !== 200) {
                $code = 400;
            }
            $generateResultBody = $generateResult->getBody();
            $runResultBody      = $runResult->getBody();
        } catch (Throwable $e) {
            $code               = 400;
            $generateResultBody = $e->getMessage();
            $runResultBody      = '';
        }

        if ($code !== 200) {
            $this->topologyManager->unPublishTopology($topology);

            return new ResponseDto(
                400,
                '',
                Json::encode(
                    [
                        'generate_result' => $generateResultBody,
                        'run_result'      => $runResultBody,
                    ],
                ),
                [],
            );
        } else {
            return new ResponseDto(200, '', Json::encode($data), []);
        }
    }

    /**
     * @param string $id
     *
     * @return string[]
     * @throws NodeException
     * @throws TopologyException
     * @throws MongoDBException
     */
    public function cloneTopology(string $id): array
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->topologyManager->cloneTopology($topology);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string    $id
     * @param bool|null $removeWithTasks
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     * @throws MongoDBException
     * @throws TopologyException
     */
    public function deleteTopology(string $id, ?bool $removeWithTasks = FALSE): ResponseDto
    {
        $topology = $this->getTopologyById($id);
        $res      = new ResponseDto(200, '', '{}', []);

        if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC->value) {
            $this->generatorBridge->stopTopology($id, TRUE);
            $res = $this->generatorBridge->deleteTopology($id);
        }

        $this->generatorBridge->invalidateTopologyCache($topology->getName());
        $this->topologyManager->deleteTopology($topology);
        if ($removeWithTasks) {
            $this->userTaskHandler->removeAllUserTasks($topology->getId());
            $headers = $this->topologyManager->getHeadersForTopologyRunRequest();
            $this->generatorBridge->removeAllLimiterAndRepeaterMessages($topology->getId(), $headers);
        }

        return $res;
    }

    /**
     * @param string $topologyId
     *
     * @return array<int, array{id: string, name: string, status: string, reason: string}>
     * @throws TopologyConfigException
     * @throws TopologyException
     */
    public function runTest(string $topologyId): array
    {
        return $this->topologyTester->testTopology($topologyId);
    }

    /**
     * @param string $topologyId
     * @param string $nodeName
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getTopologiesByIdAndNodeName(string $topologyId, string $nodeName): array
    {
        $topologies = $this->topologyManager->getTopologiesById($topologyId);
        foreach ($topologies as &$value) {
            $nodes          = array_map(static fn($node): array => [
                'id'   => $node['_id'],
                'name' => $node['name'],
            ], $this->nodeManager->getTopologyNodesByName($value['id'], $nodeName));
            $value['nodes'] = $nodes;
        }

        return $topologies;
    }

    /**
     * @param Topology $topology
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    private function getTopologyData(Topology $topology): array
    {
        $settings  = [];
        $cronNodes = $this->dm->getRepository(Node::class)->getCronNodes($topology);
        foreach ($cronNodes as $node) {
            if ($node->getCron()) {
                $settings[] = ['cron' => $node->getCron(), 'cronParams' => $node->getCronParams()];
            }
        }

        return [
            'category'     => $topology->getCategory(),
            'cronSettings' => $settings,
            'description'  => $topology->getDescr(),
            'enabled'      => $topology->isEnabled(),
            'name'         => $topology->getName(),
            'status'       => $topology->getStatus(),
            'type'         => count($cronNodes) >= 1 ? TypeEnum::CRON->value : TypeEnum::WEBHOOK->value,
            'version'      => $topology->getVersion(),
            'visibility'   => $topology->getVisibility(),
            '_id'          => $topology->getId(),
        ];
    }

    /**
     * @param string $id
     *
     * @return Topology
     * @throws TopologyException
     */
    private function getTopologyById(string $id): Topology
    {
        /** @var Topology|null $res */
        $res = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $id]);

        if (!$res) {
            throw new TopologyException(
                sprintf('Topology with [%s] id was not found.', $id),
                TopologyException::TOPOLOGY_NOT_FOUND,
            );
        }

        return $res;
    }

}
