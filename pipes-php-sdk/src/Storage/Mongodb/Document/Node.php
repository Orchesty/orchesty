<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb\Document;

use Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Storage\Mongodb\DocumentAbstract;

/**
 * Class Node
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb\Document
 */
final class Node extends DocumentAbstract
{

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected ?string $systemConfigs = NULL;

    /**
     * Node constructor.
     *
     * @param mixed[]|null $data
     */
    public function __construct(?array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * @param SystemConfigDto|null $dto
     *
     * @return $this
     */
    public function setSystemConfigs(?SystemConfigDto $dto): Node
    {
        $this->systemConfigs = $dto?->toString();

        return $this;
    }

    /**
     * @return SystemConfigDto|null
     */
    public function getSystemConfigs(): ?SystemConfigDto
    {
        if (!$this->systemConfigs) {
            return NULL;
        }

        return SystemConfigDto::fromString($this->systemConfigs);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            '_id'         => $this->getId(),
            'systemConfigs'        => $this->getSystemConfigs(),
        ];
    }

    /**
     * @param mixed[] $data
     *
     * @return $this
     */
    protected function fromArray(array $data): Node
    {
        if (array_key_exists('id', $data))
            $this->setId($data['id']);
        $this->setSystemConfigs(array_key_exists('systemConfigs', $data) ? $data['systemConfigs'] : NULL);

        return $this;
    }

}
