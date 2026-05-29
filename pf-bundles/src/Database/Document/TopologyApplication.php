<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Database\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class TopologyApplication
 *
 * @package Hanaboso\PipesFramework\Database\Document
 */
#[ODM\EmbeddedDocument]
class TopologyApplication
{

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private readonly string $key;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private readonly string $host;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private readonly string $sdk;

    /**
     * TopologyApplication constructor.
     *
     * @param string $key
     * @param string $host
     * @param string $sdk
     */
    public function __construct(string $key, string $host, string $sdk)
    {
        $this->key  = $key;
        $this->host = $host;
        $this->sdk  = $sdk;
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

    /**
     * @return string
     */
    public function getSdk(): string
    {
        return $this->sdk;
    }

}
