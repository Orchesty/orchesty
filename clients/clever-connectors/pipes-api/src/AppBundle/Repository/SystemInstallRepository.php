<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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
     * @param array $headers
     *
     * @return SystemInstall
     * @throws LogicException
     */
    public function getSystemInstallFromHeaders(array $headers): SystemInstall
    {
        $user   = CMHeaders::get(CMHeaders::GUID, $headers) ?? '';
        $token  = CMHeaders::get(CMHeaders::TOKEN, $headers) ?? '';
        $system = CMHeaders::get(CMHeaders::SYSTEM_KEY, $headers) ?? '';

        if (empty($user) || empty($token) || empty($system)) {
            throw new LogicException('User or Token or System is missing in header.');
        }

        return $this->getSystemInstall($user, $token, $system);
    }

    /**
     * @param string $user
     * @param string $token
     * @param string $systemKey
     *
     * @return SystemInstall
     * @throws LogicException
     */
    public function getSystemInstall(string $user, string $token, string $systemKey): SystemInstall
    {
        $this->getDocumentManager()->clear(SystemInstall::class);
        /** @var SystemInstall $ret */
        $ret = $this->createQueryBuilder()
            ->field('user')->equals($user)
            ->field('token')->equals($token)
            ->field('system')->equals($systemKey)
            ->getQuery()->getSingleResult();

        if (!$ret || empty($ret)) {
            $message = 'SystemInstall not found for [user="%s"], [token="%s"], [systemKey="%s"]!';
            throw new LogicException(sprintf($message, $user, $token, $systemKey));
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

    /**
     * @param DateTime $dateTime
     *
     * @return array
     */
    public function findBeforeExpiration(DateTime $dateTime): array
    {
        $query = $this->createQueryBuilder()->find()
            ->field('expires')->notEqual(NULL)
            ->field('expires')->lte($dateTime)
            ->getQueryArray();

        $cursor = $this->dm->getDocumentCollection($this->getClassName())
            ->find($query);

        return $cursor->toArray();
    }

}