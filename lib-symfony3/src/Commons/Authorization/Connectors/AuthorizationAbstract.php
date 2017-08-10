<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Authorization\Connectors;

use Hanaboso\PipesFramework\Commons\BaseService\BaseServiceInterface;
use Hanaboso\PipesFramework\Commons\ServiceStorage\ServiceStorageInterface;

/**
 * Class AuthorizationAbstract
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\Connectors
 */
abstract class AuthorizationAbstract implements AuthorizationInterface, BaseServiceInterface
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var ServiceStorageInterface
     */
    protected $serviceStorage;

    /**
     * AuthorizationAbstract constructor.
     *
     * @param string                  $id
     * @param ServiceStorageInterface $serviceStorage
     */
    public function __construct(string $id, ServiceStorageInterface $serviceStorage)
    {
        $this->id             = $id;
        $this->serviceStorage = $serviceStorage;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getServiceType(): string
    {
        return self::AUTHORIZATION;
    }

}