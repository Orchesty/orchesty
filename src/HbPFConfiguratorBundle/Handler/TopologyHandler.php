<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Database\Document\Topology;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Database\Repository\NodeRepository;
use Hanaboso\CommonsBundle\Database\Repository\TopologyRepository;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\CommonsBundle\Utils\UriParams;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Throwable;

/**
 * Class TopologyHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class TopologyHandler
{

    /**
     * @var ObjectRepository|TopologyRepository
     */
    protected $topologyRepository;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var TopologyManager
     */
    protected $manager;

    /**
     * @var CurlManagerInterface
     */
    protected $curlManager;

    /**
     * @var TopologyGeneratorBridge
     */
    protected $generatorBridge;

    /**
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator  $dml
     * @param TopologyManager         $manager
     * @param TopologyGeneratorBridge $generatorBridge
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        TopologyManager $manager,
        TopologyGeneratorBridge $generatorBridge
    )
    {
        /** @var DocumentManager $dm */
        $dm                       = $dml->getDm();
        $this->dm                 = $dm;
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
        $this->manager            = $manager;
        $this->generatorBridge    = $generatorBridge;
    }

    /**
     * @param int  $limit
     * @param int  $offset
     * @param null $orderBy
     *
     * @return array
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
     * @return array
     * @throws CurlException
     * @throws CronException
     */
    public function getCronTopologies(): array
    {
        return ['items' => $this->manager->getCronTopologies()];
    }

    /**
     * @param string $id
     *
     * @return array
     * @throws TopologyException
     */
    public function getTopology(string $id): array
    {
        $topology = $this->getTopologyById($id);

        return $this->getTopologyData($topology);
    }

    /**
     * @param array $data
     *
     * @return array
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
     * @param string $id
     * @param array  $data
     *
     * @return array
     * @throws TopologyException
     * @throws MongoDBException
     * @throws CurlException
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
     * @param string $id
     * @param string $content
     * @param array  $data
     *
     * @return array
     * @throws CronException
     * @throws CurlException
     * @throws EnumException
     * @throws NodeException
     * @throws TopologyException
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
                400, '',
                (string) json_encode(
                    [
                        'generate_result' => $generateResultBody,
                        'run_result'      => $runResultBody,
                    ],
                    JSON_THROW_ON_ERROR
                ),
                []
            );
        } else {
            return new ResponseDto(200, '', (string) json_encode($data, JSON_THROW_ON_ERROR), []);
        }
    }

    /**
     * @param string $id
     *
     * @return string[]
     * @throws EnumException
     * @throws NodeException
     * @throws TopologyException
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
     */
    public function deleteTopology(string $id): ResponseDto
    {
        $topology = $this->getTopologyById($id);
        $res      = new ResponseDto(200, '', '', []);

        if (!($topology->getVisibility() === TopologyStatusEnum::PUBLIC && $topology->isEnabled())) {
            $this->generatorBridge->stopTopology($id);
            $res = $this->generatorBridge->deleteTopology($id);
        }

        $this->manager->deleteTopology($topology);
        $this->generatorBridge->invalidateTopologyCache($topology->getName());

        return $res;
    }

    /**
     * @param string $topologyId
     *
     * @return array
     * @throws CurlException
     * @throws TopologyConfigException
     * @throws TopologyException
     * @throws LockException
     * @throws MappingException
     */
    public function runTest(string $topologyId): array
    {
        $startTopology = TRUE;
        $runningInfo   = $this->generatorBridge->infoTopology($topologyId);
        if ($runningInfo instanceof ResponseDto && $runningInfo->getBody()) {
            $result = json_decode($runningInfo->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
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
     * @return array
     */
    private function getTopologyData(Topology $topology): array
    {
        /** @var NodeRepository $repository */
        $repository = $this->dm->getRepository(Node::class);

        return [
            '_id'        => $topology->getId(),
            'type'       => $repository->getTopologyType($topology),
            'name'       => $topology->getName(),
            'descr'      => $topology->getDescr(),
            'status'     => $topology->getStatus(),
            'visibility' => $topology->getVisibility(),
            'version'    => $topology->getVersion(),
            'category'   => $topology->getCategory(),
            'enabled'    => $topology->isEnabled(),
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
                TopologyException::TOPOLOGY_NOT_FOUND
            );
        }

        return $res;
    }

}
