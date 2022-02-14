<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
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
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\String\UriParams;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Validations\Validations;
use JsonException;
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
     * @param TopologyManager         $manager
     * @param TopologyGeneratorBridge $generatorBridge
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        protected TopologyManager $manager,
        protected TopologyGeneratorBridge $generatorBridge,
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
        [$started, $errors] = $this->manager->runTopology($topologyId, $data[self::STARTING_POINTS], $data[self::BODY]);

        return [
            'started' => $started,
            'state'   => $errors ? 'nok' : 'ok',
            'errors'  => $errors,
        ];
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
     * @throws JsonException
     */
    public function getCronTopologies(): array
    {
        $cron = $this->manager->getCronTopologies();

        return [
            'items'  => $cron,
            'paging' => [
                'page'         => 1,
                'itemsPerPage' => 50,
                'total'        => count($cron),
                'nextPage'     => 2,
                'lastPage'     => 2,
                'previousPage' => 1,
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

        $topology = $this->manager->createTopology($data);

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
     * @throws JsonException
     */
    public function updateTopology(string $id, array $data): array
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->manager->updateTopology($topology, $data);

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
     * @throws JsonException
     */
    public function saveTopologySchema(string $id, string $content, array $data): array
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->manager->saveTopologySchema($topology, $content, $data);

        return $this->getTopologyData($topology);
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
        $topology = $this->manager->publishTopology($topology);
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
            $this->manager->unPublishTopology($topology);

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
     * @throws JsonException
     */
    public function cloneTopology(string $id): array
    {
        $topology = $this->getTopologyById($id);
        $topology = $this->manager->cloneTopology($topology);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string $id
     *
     * @return ResponseDto
     * @throws CronException
     * @throws CurlException
     * @throws TopologyException
     * @throws MongoDBException
     * @throws JsonException
     */
    public function deleteTopology(string $id): ResponseDto
    {
        $topology = $this->getTopologyById($id);
        $res      = new ResponseDto(200, '', '{}', []);

        if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
            $this->generatorBridge->stopTopology($id);
            $res = $this->generatorBridge->deleteTopology($id);
        }

        $this->generatorBridge->invalidateTopologyCache($topology->getName());
        $this->manager->deleteTopology($topology);

        return $res;
    }

    /**
     * @param string $topologyId
     *
     * @return mixed[]
     * @throws CurlException
     * @throws TopologyConfigException
     * @throws TopologyException
     * @throws LockException
     * @throws MappingException
     * @throws JsonException
     */
    public function runTest(string $topologyId): array
    {
        $startTopology = TRUE;
        $runningInfo   = $this->generatorBridge->infoTopology($topologyId);
        if ($runningInfo->getBody()) {
            $result = Json::decode($runningInfo->getBody());
            if (array_key_exists('docker_info', $result) && count($result['docker_info'])) {
                $startTopology = FALSE;
            }
        }

        if ($startTopology) {
            $this->generatorBridge->generateTopology($topologyId);
            $this->generatorBridge->runTopology($topologyId);
            sleep(3); // Wait for topology start...
        }

        $res = $this->generatorBridge->runTest($topologyId);

        $topology = $this->getTopologyById($topologyId);
        if ($topology->getVisibility() === TopologyStatusEnum::DRAFT) {
            $this->generatorBridge->stopTopology($topologyId);
            $this->generatorBridge->deleteTopology($topologyId);
        }

        return $res;
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
            if($node->getCron() && $node->getCronParams()){
                $settings[] = ['cron' => $node->getCron(), 'cronParams' => $node->getCronParams()];
            }
        }

        return [
            '_id'          => $topology->getId(),
            'type'         => count($cronNodes) >= 1 ? TypeEnum::CRON : TypeEnum::WEBHOOK,
            'name'         => $topology->getName(),
            'descr'        => $topology->getDescr(),
            'status'       => $topology->getStatus(),
            'visibility'   => $topology->getVisibility(),
            'version'      => $topology->getVersion(),
            'category'     => $topology->getCategory(),
            'enabled'      => $topology->isEnabled(),
            'cronSettings' => $settings,
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
