<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress as BaseTopologyProgress;

/**
 * Class TopologyProgress
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Configurator\Document
 */
#[ODM\Document(
    collection: 'MultiCounter',
    repositoryClass: 'Hanaboso\PipesFramework\Configurator\Repository\TopologyProgressRepository',
)]
#[ODM\Index(keys: ['auditData' => 'asc'], name: 'IK_multi_counter_audit_data')]
class TopologyProgress extends BaseTopologyProgress
{

    /**
     * @var string[]
     */
    #[ODM\Field(type: 'collection')]
    private array $auditData = [];

    /**
     * @return string[]
     */
    public function getAuditData(): array
    {
        return $this->auditData;
    }

    /**
     * @param string[] $auditData
     *
     * @return TopologyProgress
     */
    public function setAuditData(array $auditData): self
    {
        $this->auditData = $auditData;

        return $this;
    }

}
