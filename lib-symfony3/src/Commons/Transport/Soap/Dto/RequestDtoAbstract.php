<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Soap\Dto;

use Hanaboso\PipesFramework\Commons\Transport\Soap\SoapException;

/**
 * Class RequestDto
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Soap\Dto
 */
abstract class RequestDtoAbstract
{

    /**
     * @var int
     */
    protected $version;

    /**
     * @var string|null
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var string
     */
    private $function;

    /**
     * @var array
     */
    private $arguments;

    /**
     * RequestDtoAbstract constructor.
     *
     * @param string $function
     * @param array  $arguments
     */
    public function __construct(string $function, array $arguments = [])
    {
        $this->function  = $function;
        $this->arguments = $arguments;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return $this
     * @throws SoapException
     */
    public function setVersion(int $version)
    {
        if (!in_array($version, [SOAP_1_1, SOAP_1_2])) {
            throw new SoapException(
                sprintf('Unknown SOAP version "%s".', $version),
                SoapException::UNKNOWN_SOAP_VERSION
            );
        }

        $this->version = $version;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @param string $password
     *
     * @return $this
     */
    public function setAuth(string $user, string $password): self
    {
        $this->user     = $user;
        $this->password = $password;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

}