<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFAclBundle\Provider;

use Hanaboso\PipesFramework\HbPFAclBundle\Exception\AclException;

/**
 * Class ResourceProvider
 *
 * @package Hanaboso\PipesFramework\Acl\Provider
 */
class ResourceProvider
{

    /**
     * @var array
     */
    private $resources;

    /**
     * ResourceProvider constructor.
     *
     * @param array $rules
     *
     * @throws AclException
     */
    public function __construct(array $rules)
    {
        if (!isset($rules['resources'])) {
            throw new AclException('ACL resources not exist', AclException::ACL_NOT_EXIST);
        }

        if (!is_array($rules['resources'])) {
            throw new AclException('ACL resources not array', AclException::ACL_NOT_ARRAY);
        }

        $this->resources = $rules['resources'];
    }

    /**
     * @return array
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasResource(string $key): bool
    {
        return isset($this->resources[$key]);
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws AclException
     */
    public function getResource(string $key): string
    {
        if (!isset($this->resources[$key])) {
            throw new AclException(
                sprintf('ACL resource \'%s\' not exist', $key),
                AclException::RESOURCE_NOT_EXIST
            );
        }

        return $this->resources[$key];
    }

}