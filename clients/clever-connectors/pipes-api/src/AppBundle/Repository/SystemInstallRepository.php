<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Doctrine\ODM\MongoDB\DocumentRepository;
use LogicException;

/**
 * Class SystemInstallRepository
 *
 * @package CleverConnectors\AppBundle\Repository
 */
class SystemInstallRepository extends DocumentRepository
{

    /**
     * @param string $user
     * @param string $token
     * @param string $systemKey
     *
     * @return SystemInstall
     */
    public function getSystemInstall(string $user, string $token, string $systemKey): SystemInstall
    {
        /** @var SystemInstall $ret */
        $ret = $this->createQueryBuilder()
            ->field('user')->equals($user)
            ->field('token')->equals($token)
            ->field('system')->equals($systemKey)
            ->getQuery()->getSingleResult();

        if (!$ret || empty($ret)) {
            throw new LogicException('SystemInstall not found!');
        }

        return $ret;
    }

}