<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\AclBundle\Document\Rule;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager as BaseTopologyManager;
use Hanaboso\PipesFramework\Database\Document\Topology as BaseTopology;
use Hanaboso\PipesFrameworkEnterprise\Database\Document\Topology;
use Hanaboso\PipesFrameworkEnterprise\ResourceEnum;

/**
 * Class TopologyManager
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Model
 */
final class TopologyManager extends BaseTopologyManager
{

    /**
     * @var ObjectRepository<Rule>
     */
    private ObjectRepository $ruleRepository;

    private DocumentManager $dm;

    /**
     * TopologyManager constructor.
     *
     * @param DatabaseManagerLocator     $dml
     * @param CronManager                $cronManager
     * @param bool                       $checkInfiniteLoop
     * @param CurlManagerInterface       $curl
     * @param string                     $startingPointHost
     * @param string                     $tunnelProxyHost
     * @param class-string<BaseTopology> $topologyClass
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        CronManager $cronManager,
        bool $checkInfiniteLoop,
        CurlManagerInterface $curl,
        string $startingPointHost,
        string $tunnelProxyHost = '',
        string $topologyClass = Topology::class,
    )
    {
        parent::__construct($dml, $cronManager, $checkInfiniteLoop, $curl, $startingPointHost, $tunnelProxyHost, $topologyClass);

        /** @var DocumentManager $dm */
        $dm                   = $dml->getDm();
        $this->dm             = $dm;
        $this->ruleRepository = $dm->getRepository(Rule::class);
    }

    /**
     * @param BaseTopology $topology
     *
     * @return void
     * @throws CronException
     * @throws CurlException
     * @throws MongoDBException
     */
    public function deleteTopology(BaseTopology $topology): void
    {
        $topologyName = $topology->getName();

        parent::deleteTopology($topology);

        $this->cleanupTopologyAclRules($topologyName);
    }

    /**
     * @param BaseTopology $topology
     * @param mixed[]      $data
     *
     * @return BaseTopology
     * @throws MongoDBException
     * @throws TopologyException
     */
    public function updateTopology(BaseTopology $topology, array $data): BaseTopology
    {
        $name = $topology->getName();

        $topology = parent::updateTopology($topology, $data);

        if ($topology->getName() !== $name) {
            $this->migrateTopologyAclRules($name, $topology->getName());
        }

        return $topology;
    }

    /**
     * @param BaseTopology $topology
     * @param mixed[]      $data
     *
     * @return BaseTopology
     */
    protected function setTopologyData(BaseTopology $topology, array $data): BaseTopology
    {
        $topology = parent::setTopologyData($topology, $data);

        if ($topology instanceof Topology && isset($data['mcp_description'])) {
            $topology->setMcpDescription($data['mcp_description']);
        }

        return $topology;
    }

    /**
     * @param BaseTopology $topology
     * @param string       $hash
     *
     * @return BaseTopology
     */
    protected function cloneTopologyShallow(BaseTopology $topology, string $hash): BaseTopology
    {
        $clonedTopology = parent::cloneTopologyShallow($topology, $hash);

        if ($clonedTopology instanceof Topology && $topology instanceof Topology) {
            $clonedTopology->setMcpDescription($topology->getMcpDescription());
        }

        return $clonedTopology;
    }

    /**
     * @param string $topologyName
     *
     * @return void
     * @throws MongoDBException
     */
    private function cleanupTopologyAclRules(string $topologyName): void
    {
        $suffix = sprintf(':%s', $topologyName);

        foreach (ResourceEnum::TOPOLOGY_SCOPED_PREFIXES as $prefix) {
            $rules = $this->ruleRepository->findBy(['resource' => sprintf('%s%s', $prefix, $suffix)]);

            foreach ($rules as $rule) {
                $this->dm->remove($rule);
            }
        }

        $this->dm->flush();
    }

    /**
     * @param string $oldName
     * @param string $newName
     *
     * @return void
     * @throws MongoDBException
     */
    private function migrateTopologyAclRules(string $oldName, string $newName): void
    {
        foreach (ResourceEnum::TOPOLOGY_SCOPED_PREFIXES as $prefix) {
            $oldResource = sprintf('%s:%s', $prefix, $oldName);
            $newResource = sprintf('%s:%s', $prefix, $newName);

            foreach ($this->ruleRepository->findBy(['resource' => $oldResource]) as $rule) {
                $rule->setResource($newResource);
            }
        }

        $this->dm->flush();
    }

}
