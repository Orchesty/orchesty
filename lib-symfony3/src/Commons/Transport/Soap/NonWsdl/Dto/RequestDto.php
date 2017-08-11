<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\NonWsdl\Dto;

use Hanaboso\PipesFramework\Commons\Transport\Soap\Dto\RequestDtoAbstract;

/**
 * Class RequestDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\NonWsdl\Dto
 */
class RequestDto extends RequestDtoAbstract
{

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * RequestDto constructor.
     *
     * @param string $function
     * @param array  $arguments
     * @param string $uri
     * @param string $namespace
     */
    public function __construct(string $function, array $arguments = [], string $uri, string $namespace)
    {
        parent::__construct($function, $arguments);

        $this->uri       = $uri;
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

}