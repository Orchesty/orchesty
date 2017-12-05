<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Commons\Utils\UriParams;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;

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
     * @var GeneratorHandler
     */
    protected $generatorHandler;

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
     * @param GeneratorHandler       $generatorHandler
     * @param RequestHandler         $requestHandler
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        TopologyManager $manager,
        GeneratorHandler $generatorHandler,
        RequestHandler $requestHandler
    )
    {
        $this->dm                 = $dml->getDm();
        $this->topologyRepository = $this->dm->getRepository(Topology::class);
        $this->manager            = $manager;
        $this->generatorHandler   = $generatorHandler;
        $this->requestHandler     = $requestHandler;
    }

    /**
     * @param int  $limit
     * @param int  $offset
     * @param null $orderBy
     *
     * @return array
     */
    public function getTopologies(?int $limit = NULL, ?int $offset = NULL, $orderBy = NULL): array
    {
        $sort       = UriParams::parseOrderBy($orderBy);
        $topologies = $this->topologyRepository->findBy([], $sort, $limit, $offset);

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
     */
    public function createTopology(array $data): array
    {
        ControllerUtils::checkParameters(['name', 'descr'], $data);

        $topology = $this->manager->createTopology($data);

        return $this->getTopologyData($topology);

    }

    /**
     * @param string $id
     * @param array  $data
     *
     * @return array
     */
    public function updateTopology(string $id, array $data): array
    {
        $topology = $this->getTopologyById($id);
        $this->manager->updateTopology($topology, $data);

        return $this->getTopologyData($topology);
    }

    /**
     * @param string $id
     *
     * @return string
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
     */
    public function publishTopology(string $id): ResponseDto
    {
        $topology = $this->getTopologyById($id);
        $this->manager->publishTopology($topology);
        $data = $this->getTopologyData($topology);

        $generateResult = $this->requestHandler->generateTopology($id);
        $runResult      = $this->requestHandler->runTopology($id);

        if ($generateResult->getStatusCode() !== 200 || $runResult->getStatusCode() !== 200) {
            return new ResponseDto(400, '', json_encode([
                'generate_result' => $generateResult->getBody(),
                'run_result'      => $runResult->getBody(),
            ]), []);
        } else {
            return new ResponseDto(200, '', json_encode($data), []);
        }
    }

    /**
     * @param string $id
     *
     * @return string[]
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
     */
    public function deleteTopology(string $id): ResponseDto
    {
        $topology = $this->getTopologyById($id);
        $res      = new ResponseDto(200, '', '', []);

        if ($topology->getVisibility() === TopologyStatusEnum::PUBLIC) {
            $res = $this->requestHandler->deleteTopology($id);
        }

        $this->manager->deleteTopology($topology);

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
