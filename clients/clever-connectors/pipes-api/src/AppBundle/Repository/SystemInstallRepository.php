<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
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
     * @param string $event
     * @param string $userId
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getSystemInstallByEvent(string $event, string $userId): array
    {
        SystemInstall::checkEvent($event);
        $systemInstalls = $this->createQueryBuilder()
            ->field($event)->equals(TRUE)
            ->field('user')->equals($userId)
            ->getQuery()->execute()->toArray(FALSE);

        if (!empty($systemInstalls)) {
            return $systemInstalls;
        }

        return [];
    }

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
     * @param array $headers
     *
     * @return SystemInstall
     * @throws LogicException
     */
    public function getSystemInstallFromPluginHeaders(array $headers): SystemInstall
    {
        $user   = PluginHeadersEnum::get(PluginHeadersEnum::GUID, $headers);
        $token  = PluginHeadersEnum::get(PluginHeadersEnum::TOKEN, $headers);
        $system = PluginHeadersEnum::get(PluginHeadersEnum::SYSTEM, $headers);

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
        $systemInstall
            ->setSynchronizedTime($now)
            ->setSynchronized(TRUE);
        $this->saveSystemInstall($systemInstall);
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function saveSystemInstall(SystemInstall $systemInstall): void
    {
        $expires = !$systemInstall->getExpires() ? NULL : $systemInstall->getExpires()->format(DateTime::W3C);
        $created = !$systemInstall->getCreated() ? NULL : $systemInstall->getCreated()->format(DateTime::W3C);
        $sync    = !$systemInstall->getSynchronizedTime() ? NULL : $systemInstall->getSynchronizedTime()
            ->format(DateTime::W3C);

        $this->getDocumentManager()
            ->getDocumentCollection(SystemInstall::class)
            ->update(
                ['_id' => new MongoId($systemInstall->getId())],
                [
                    SystemInstall::USER               => $systemInstall->getUser(),
                    SystemInstall::TOKEN              => $systemInstall->getToken(),
                    SystemInstall::SYSTEM             => $systemInstall->getSystem(),
                    SystemInstall::EXPIRES            => $expires,
                    SystemInstall::SYNCHRONIZED       => $systemInstall->isSynchronized(),
                    SystemInstall::SYNCHRONIZED_TIME  => $sync,
                    SystemInstall::CREATED            => $created,
                    SystemInstall::ENCRYPTED_SETTINGS => CryptManager::encrypt($systemInstall->getSettings()),
                    SystemInstall::EVENT_CREATE       => $systemInstall->isEventCreate(),
                    SystemInstall::EVENT_UNSUBSCRIBE  => $systemInstall->isEventUnsubscribe(),
                    SystemInstall::EVENT_HARD_BOUNCE  => $systemInstall->isEventHardBounce(),
                    SystemInstall::PLUGIN_VERSION     => $systemInstall->getPluginVersion(),
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