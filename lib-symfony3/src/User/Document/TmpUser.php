<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class TmpUser
 *
 * @package Hanaboso\PipesFramework\User\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\User\Repository\TmpUserRepository")
 */
class TmpUser extends UserAbstract
{

}