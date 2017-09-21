<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class SystemManager
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
class SystemManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var SystemLoader
     */
    private $systemLoader;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $systemRepository;

    /**
     * SystemManager constructor.
     *
     * @param DocumentManager $dm
     * @param SystemLoader    $systemLoader
     */
    public function __construct(DocumentManager $dm, SystemLoader $systemLoader)
    {
        $this->dm               = $dm;
        $this->systemLoader     = $systemLoader;
        $this->systemRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param string $group
     *
     * @return SystemInterface[]
     */
    public function getSystems(string $group): array
    {
        return $this->systemLoader->getSystems($group);
    }

    /**
     * @param string $user
     *
     * @return SystemInterface[]
     */
    public function getUserSystems(string $user): array
    {
        $systems = [];

        /** @var SystemInstall $systemInstall */
        foreach ($this->systemRepository->findBy(['user' => $user]) as $systemInstall) {
            $systems[] = $this->systemLoader->getSystem($systemInstall->getSystem());
        }

        return $systems;
    }

    /**
     * @param string $user
     * @param string $system
     * @param string $token
     *
     * @return SystemInstall
     */
    public function installSystem(string $user, string $system, string $token): SystemInstall
    {
        $this->systemLoader->getSystem($system);

        $systemInstall = (new SystemInstall())
            ->setUser($user)
            ->setSystem($system)
            ->setToken($token)
            ->setSynchronized(FALSE);

        $this->dm->persist($systemInstall);
        $this->dm->flush();

        return $systemInstall;
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return bool
     * @throws SystemException
     */
    public function uninstallSystem(string $user, string $system): bool
    {
        $systemInstall = $this->systemRepository->findOneBy(['user' => $user, 'system' => $system]);

        if (!$systemInstall) {
            throw new SystemException(
                sprintf('System \'%s\' not found', $system),
                SystemException::SYSTEM_NOT_FOUND
            );
        }

        $this->dm->remove($systemInstall);
        $this->dm->flush();

        return TRUE;
    }

    /**
     * @param string $user
     * @param string $system
     * @param string $token
     *
     * @return SystemInstall
     * @throws SystemException
     */
    public function switchToken(string $user, string $system, string $token): SystemInstall
    {
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemRepository->findOneBy(['user' => $user, 'system' => $system]);

        if (!$systemInstall) {
            throw new SystemException(
                sprintf('System \'%s\' not found', $system),
                SystemException::SYSTEM_NOT_FOUND
            );
        }

        $systemInstall->setToken($token);
        $this->dm->flush();

        return $systemInstall;
    }

    /**
     * @param string $system
     * @param bool   $synchronized
     *
     * @return string[]
     */
    public function getSystemUsers(string $system, bool $synchronized): array
    {
        $this->systemLoader->getSystem($system);

        $users   = [];
        $systems = $this->systemRepository->findBy(['system' => $system, 'synchronized' => $synchronized]);

        foreach ($systems as $systemInstall) {

            $users[] = $systemInstall->getUser();
        }

        return $users;
    }

}