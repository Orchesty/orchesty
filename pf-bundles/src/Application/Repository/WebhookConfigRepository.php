<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesFramework\Application\Document\WebhookConfig;

/**
 * @phpstan-extends DocumentRepository<WebhookConfig>
 */
final class WebhookConfigRepository extends DocumentRepository
{

    /**
     * @return WebhookConfig[]
     */
    public function findByTopology(string $topologyName): array
    {
        return $this->findBy(['topologyName' => $topologyName]);
    }

    public function findByTopologyAndNode(string $topologyName, string $nodeName): ?WebhookConfig
    {
        return $this->findOneBy(['topologyName' => $topologyName, 'nodeName' => $nodeName]);
    }

    /**
     * @return WebhookConfig[]
     */
    public function findEnabledByTopology(string $topologyName): array
    {
        return $this->findBy(['topologyName' => $topologyName, 'enabled' => TRUE]);
    }

}
