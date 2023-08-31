<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\Utils\String\Json;

/**
 * Class Sdk
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Configurator\Repository\SdkRepository")
 */
class Sdk
{

    use IdTrait;

    public const ID      = 'id';
    public const NAME    = 'name';
    public const URL     = 'url';
    public const HEADERS = 'headers';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $name;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $url;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private string $headers = '[]';

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
    public function setName(string $name): Sdk
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
    public function setUrl(string $url): Sdk
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
    public function setHeaders(array $headers): Sdk
    {
        $this->headers = Json::encode($headers);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::ID      => $this->id,
            self::NAME    => $this->name,
            self::URL     => $this->url,
            self::HEADERS => $this->getHeaders(),
        ];
    }

}
