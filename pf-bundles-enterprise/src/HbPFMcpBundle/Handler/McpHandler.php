<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Handler;

use Hanaboso\PipesFrameworkEnterprise\Mcp\Model\McpManager;

/**
 * Class McpHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\Handler
 */
final class McpHandler
{

    /**
     * McpHandler constructor.
     *
     * @param McpManager $manager
     */
    public function __construct(private McpManager $manager)
    {
    }

    /**
     * @return mixed[]
     */
    public function getTopologiesEntitiesManifest(): array
    {
        return $this->manager->getTopologiesEntitiesManifest();
    }

    /**
     * @return mixed[]
     */
    public function getManifest(): array
    {
        return $this->manager->getManifest();
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function run(array $data): array
    {
        return $this->manager->run($data);
    }

}
