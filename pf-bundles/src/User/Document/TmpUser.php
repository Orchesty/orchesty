<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\User\Entity\TmpUserInterface;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;

/**
 * Class TmpUser
 *
 * @package Hanaboso\PipesFramework\User\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\User\Repository\Document\TmpUserRepository")
 */
class TmpUser extends UserAbstract implements TmpUserInterface
{

    /**
     * @return string
     */
    public function getType(): string
    {
        return UserTypeEnum::TMP_USER;
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [];
    }

}