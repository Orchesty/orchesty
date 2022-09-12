<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class TopologyApplication
 *
 * @package Hanaboso\PipesPhpSdk\Database\Document
 *
 * @ODM\EmbeddedDocument()
 */
class TopologyApplication
{

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private readonly string $key;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private readonly string $host;

    /**
     * TopologyApplication constructor.
     *
     * @param string $key
     * @param string $host
     */
    public function __construct(string $key, string $host)
    {
        $this->key  = $key;
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

}
