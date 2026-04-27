<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Application\Document\WebhookConfig;

/**
 * Class WebhookConfigRepository
 *
 * @package Hanaboso\PipesFramework\Application\Repository
 *
 * @phpstan-extends DocumentRepository<WebhookConfig>
 */
final class WebhookConfigRepository extends DocumentRepository
{

    /**
     * @param string $topologyName
     *
     * @return WebhookConfig[]
     */
    public function findByTopology(string $topologyName): array
    {
        return $this->findBy(['topologyName' => $topologyName]);
    }

    /**
     * @param string $topologyName
     * @param string $nodeName
     *
     * @return WebhookConfig|null
     */
    public function findByTopologyAndNode(string $topologyName, string $nodeName): ?WebhookConfig
    {
        return $this->findOneBy(['topologyName' => $topologyName, 'nodeName' => $nodeName]);
    }

    /**
     * @param string $topologyName
     *
     * @return WebhookConfig[]
     */
    public function findEnabledByTopology(string $topologyName): array
    {
        return $this->findBy(['topologyName' => $topologyName, 'enabled' => TRUE]);
    }

}
