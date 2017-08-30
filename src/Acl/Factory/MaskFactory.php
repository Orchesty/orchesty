<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Factory;

use Hanaboso\PipesFramework\Acl\Enum\ActionEnum;
use Hanaboso\PipesFramework\Acl\Enum\PropertyEnum;
use Hanaboso\PipesFramework\Acl\Exception\AclException;

/**
 * Class MaskFactory
 *
 * @package Hanaboso\PipesFramework\Acl\Factory
 */
class MaskFactory
{

    /**
     * @param string[] $data
     *
     * @return int
     * @throws AclException
     */
    public function maskAction(array $data): int
    {
        if (!isset($data[ActionEnum::DELETE]) || !isset($data[ActionEnum::READ]) || !isset($data[ActionEnum::WRITE])
        ) {
            throw new AclException(
                'Missing data',
                AclException::MISSING_DATA
            );
        }

        $mask = boolval($data[ActionEnum::DELETE]) << 2 | boolval($data[ActionEnum::WRITE]) << 1 | boolval($data[ActionEnum::READ]);
        if ($mask === 0) {
            throw new AclException(
                'Sent mask has no value',
                AclException::ZERO_MASK
            );
        }

        return $mask;
    }

    /**
     * @param string[] $data
     *
     * @return int
     * @throws AclException
     */
    public function maskProperty(array $data): int
    {
        if (!isset($data[PropertyEnum::OWNER]) || !isset($data[PropertyEnum::GROUP])) {
            throw new AclException(
                'Missing data',
                AclException::MISSING_DATA
            );
        }

        $mask = boolval($data[PropertyEnum::GROUP]) ? 2 : (boolval($data[PropertyEnum::OWNER]) ? 1 : 0);
        if ($mask === 0) {
            throw new AclException(
                'Sent mask has no value',
                AclException::ZERO_MASK
            );
        }

        return $mask;
    }

}