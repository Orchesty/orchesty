<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Database\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Database\Document\Topology as BaseTopology;
use Hanaboso\Utils\String\Json;

/**
 * Class Topology
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Database\Document
 */
#[ODM\Document(
    collection: 'Topology',
    repositoryClass: 'Hanaboso\PipesFramework\Database\Repository\TopologyRepository',
)]
class Topology extends BaseTopology
{

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    protected string $mcpDescription = '';

    /**
     * @return mixed[]
     */
    public function getMcpDescription(): array
    {
        return $this->mcpDescription ? Json::decode($this->mcpDescription) : [];
    }

    /**
     * @param mixed[] $mcpDescription
     *
     * @return Topology
     */
    public function setMcpDescription(array $mcpDescription): self
    {
        $this->mcpDescription = Json::encode($mcpDescription);

        return $this;
    }

}
