<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\CommonsBundle\Utils\UriParams;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
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
     * @var RequestHandler
     */
    protected $requestHandler;

    /**
     * TopologyHandler constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param TopologyManager        $manager
     * @param RequestHandler         $requestHandler
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        TopologyManager $manager,
        RequestHandler $requestHandler
    )
    {
        /** @var DocumentManager $dm */
        $dm                       = $dml->getDm();
        $this->dm                 = $dm;
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
        $this->manager            = $manager;
        $this->requestHandler     = $requestHandler;
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

        $this->requestHandler->invalidateTopologyCache($topology->getName());

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
     * @return string[]
     * @throws NodeException
     * @throws TopologyException
     * @throws EnumException
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
            $generateResult = $this->requestHandler->generateTopology($id);
            $runResult      = $this->requestHandler->runTopology($id);
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

            return new ResponseDto(400, '', (string) json_encode([
                'generate_result' => $generateResultBody,
                'run_result'      => $runResultBody,
            ]), []);
        } else {
            return new ResponseDto(200, '', (string) json_encode($data), []);
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
     * @throws TopologyException
     * @throws CurlException
     */
    public function deleteTopology(string $id): ResponseDto
    {
        $topology = $this->getTopologyById($id);
        $res      = new ResponseDto(200, '', '', []);

        if (!($topology->getVisibility() === TopologyStatusEnum::PUBLIC && $topology->isEnabled())) {
            $res = $this->requestHandler->deleteTopology($id);
        }

        $this->manager->deleteTopology($topology);
        $this->requestHandler->invalidateTopologyCache($topology->getName());

        return $res;
    }

    /**
     * @param string $topologyId
     *
     * @return array
     * @throws CurlException
     * @throws TopologyException
     */
    public function runTest(string $topologyId): array
    {
        $startTopology = TRUE;
        $runningInfo   = $this->requestHandler->infoTopology($topologyId);
        if ($runningInfo instanceof ResponseDto && $runningInfo->getBody()) {
            $result = json_decode($runningInfo->getBody(), TRUE);
            if (array_key_exists('docker_info', $result) && count($result['docker_info'])) {
                $startTopology = FALSE;
            }
        }

        if ($startTopology) {
            $this->requestHandler->generateTopology($topologyId);
            $this->requestHandler->runTopology($topologyId);
        }

        $res = $this->requestHandler->runTest($topologyId);

        $topology = $this->getTopologyById($topologyId);
        if ($topology->getVisibility() === TopologyStatusEnum::DRAFT) {
            $this->requestHandler->deleteTopology($topologyId);
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
        return [
            '_id'        => $topology->getId(),
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
