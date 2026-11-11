<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class LimiterMessage
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 */
#[ODM\EmbeddedDocument]
class LimiterMessage
{

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $body;

    /**
     * @var mixed[]
     */
    #[ODM\Field(type: 'hash')]
    private array $headers;

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return mixed[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

}
