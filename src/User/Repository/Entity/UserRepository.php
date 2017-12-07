<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Repository\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Class UserRepository
 *
 * @package Hanaboso\PipesFramework\User\Repository\Entity
 */
class UserRepository extends EntityRepository
{

    /**
     * @return array
     */
    public function getArrayOfUsers(): array
    {
        $arr = $this->createQueryBuilder('u')
            ->select(['u.email', 'u.created'])
            ->where('u.deleted = 0')
            ->getQuery()
            ->getArrayResult();

        foreach ($arr as $index => $row) {
            $arr[$index]['created'] = $row['created']->format('d-m-Y');
        }

        return $arr;
    }

}