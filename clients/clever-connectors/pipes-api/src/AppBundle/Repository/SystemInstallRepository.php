<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\SystemInstall;
use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use LogicException;
use MongoId;

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

    /**
     * @param SystemInstall $systemInstall
     */
    public function setSyncTime(SystemInstall $systemInstall): void
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->getDocumentManager()
            ->getDocumentCollection(SystemInstall::class)
            ->update(
                ['_id' => new MongoId($systemInstall->getId())],
                [
                    SystemInstall::USER               => $systemInstall->getUser(),
                    SystemInstall::TOKEN              => $systemInstall->getToken(),
                    SystemInstall::SYSTEM             => $systemInstall->getSystem(),
                    SystemInstall::CREATED            => $systemInstall->getCreated()->format(DateTime::W3C),
                    SystemInstall::SYNCHRONIZED       => TRUE,
                    SystemInstall::SYNCHRONIZED_TIME  => $now->format(DateTime::W3C),
                    SystemInstall::ENCRYPTED_SETTINGS => CryptManager::encrypt([]),
                ]
            );
    }

}