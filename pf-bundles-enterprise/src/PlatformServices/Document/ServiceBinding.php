<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class ServiceBinding
 *
 * @package Hanaboso\PipesFrameworkEnterprise\PlatformServices\Document
 */
#[ODM\Document(
    repositoryClass: 'Hanaboso\PipesFrameworkEnterprise\PlatformServices\Repository\ServiceBindingRepository',
)]
#[ODM\UniqueIndex(keys: ['serviceType' => 'asc'])]
final class ServiceBinding
{

    use IdTrait;

    public const string SERVICE_TYPE    = 'serviceType';
    public const string APPLICATION_KEY = 'applicationKey';
    public const string USER            = 'user';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $serviceType;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $applicationKey;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $user = 'system';

    /**
     * @return string
     */
    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    /**
     * @param string $serviceType
     *
     * @return ServiceBinding
     */
    public function setServiceType(string $serviceType): self
    {
        $this->serviceType = $serviceType;

        return $this;
    }

    /**
     * @return string
     */
    public function getApplicationKey(): string
    {
        return $this->applicationKey;
    }

    /**
     * @param string $applicationKey
     *
     * @return ServiceBinding
     */
    public function setApplicationKey(string $applicationKey): self
    {
        $this->applicationKey = $applicationKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return ServiceBinding
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'id'                  => $this->getId(),
            self::APPLICATION_KEY => $this->applicationKey,
            self::SERVICE_TYPE    => $this->serviceType,
            self::USER            => $this->user,
        ];
    }

}
