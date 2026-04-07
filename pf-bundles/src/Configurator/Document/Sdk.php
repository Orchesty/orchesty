<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\String\Json;

/**
 * Class Sdk
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\PipesFramework\Configurator\Repository\SdkRepository')]
class Sdk
{

    use IdTrait;

    public const string ID      = 'id';
    public const string NAME    = 'name';
    public const string URL     = 'url';
    public const string HEADERS = 'headers';
    public const string TYPE    = 'type';

    public const string TYPE_HTTP   = 'http';
    public const string TYPE_TUNNEL = 'tunnel';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $name;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $url;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $headers = '[]';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $type = self::TYPE_HTTP;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Sdk
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Sdk
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getHeaders(): array
    {
        return Json::decode($this->headers);
    }

    /**
     * @param mixed[] $headers
     *
     * @return Sdk
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = Json::encode($headers);

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Sdk
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTunnel(): bool
    {
        return $this->type === self::TYPE_TUNNEL;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::HEADERS => $this->getHeaders(),
            self::ID      => $this->id,
            self::NAME    => $this->name,
            self::TYPE    => $this->type,
            self::URL     => $this->url,
        ];
    }

}
