<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth1Interface;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Utils\DateTimeUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;

/**
 * Class SystemHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
class SystemHandler
{

    /**
     * @var SystemManager
     */
    private $manager;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * SystemHandler constructor.
     *
     * @param SystemManager   $manager
     * @param DocumentManager $dm
     */
    public function __construct(SystemManager $manager, DocumentManager $dm)
    {
        $this->manager = $manager;
        $this->dm      = $dm;
    }

    /**
     * @param string $key
     *
     * @return array
     * @throws SystemException
     */
    public function getSystem(string $key): array
    {
        return $this->manager->getSystem($key)->toArray();
    }

    /**
     * @param null|string $user
     * @param null|string $group
     *
     * @return array
     * @throws SystemException
     */
    public function getSystems(?string $user = NULL, ?string $group = NULL): array
    {
        $systems = [];

        foreach ($this->manager->getSystems($user, $group) as $system) {
            $systems[] = $system->toArray();
        }

        return $systems;
    }

    /**
     * @param string $user
     *
     * @return array
     * @throws SystemException
     */
    public function getUserSystems(string $user): array
    {
        $systems = [];
        foreach ($this->manager->getUserSystems($user) as $system) {
            $systems[] = $system->toArray($this->manager->getSystemInstall($user, $system->getKey()));
        }

        return $systems;
    }

    /**
     * @param string $user
     * @param string $systemKey
     *
     * @return array
     * @throws SystemException
     */
    public function getUserSystem(string $user, string $systemKey): array
    {
        $systemInstall = $this->manager->getSystemInstall($user, $systemKey);

        return $this->manager->getUserSystem($systemInstall);
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function installSystem(string $user, string $system, array $data): array
    {
        ControllerUtils::checkParameters(['token'], $data);

        if ($this->isSystemInstalled($user, $system)) {
            throw new CleverConnectorsException(
                'Requested system has already been installed for current user.',
                CleverConnectorsException::SYSTEM_ALREADY_INSTALLED
            );
        }

        $systemInstall = $this->manager->installSystem($user, $system, $data['token']);

        return $this->manager->getUserSystem($systemInstall);
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     * @throws SystemException
     * @throws CleverConnectorsException
     */
    public function saveSystemSettings(string $user, string $system, array $data): array
    {
        $this->manager->saveSystemSettings($user, $system, $data);

        return [];
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return array
     * @throws SystemException
     */
    public function uninstallSystem(string $user, string $system): array
    {
        $this->manager->uninstallSystem($user, $system);

        return [];
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function switchToken(string $user, string $system, array $data): array
    {
        ControllerUtils::checkParameters(['token'], $data);
        $this->manager->switchToken($user, $system, $data['token']);

        return [];
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return array
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function synchronizeSubscriptions(string $user, string $system): array
    {
        $runningTopologies = $this->manager->synchronizeSubscriptions($user, $system);

        return ['running_topologies' => $runningTopologies];
    }

    /**
     * @param string $user
     * @param string $system
     * @param array  $data
     *
     * @return array
     * @throws SystemException
     */
    public function setPassword(string $user, string $system, array $data): array
    {
        ControllerUtils::checkParameters(['password'], $data);
        $this->manager->setPassword($user, $system, $data['password']);

        return [];
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param string $redirectUrl
     *
     * @throws SystemException
     */
    public function authorize(string $user, string $systemKey, string $redirectUrl): void
    {
        $this->manager->authorize($user, $systemKey, $redirectUrl);
    }

    /**
     * @param string $user
     * @param string $systemKey
     * @param array  $data
     *
     * @return string
     * @throws SystemException
     */
    public function saveToken(string $user, string $systemKey, array $data): string
    {
        $systemInstall = $this->manager->saveToken($user, $systemKey, $data);

        return $systemInstall->getSettings()[OAuth1Interface::FRONTEND_REDIRECT_URL];
    }

    /**
     * @param string $user
     * @param string $system
     *
     * @return bool
     */
    public function isSystemInstalled(string $user, string $system): bool
    {
        $systemInstall = $this->dm->getRepository(SystemInstall::class)->findOneBy([
            'user'   => $user,
            'system' => $system,
        ]);

        return $systemInstall ? TRUE : FALSE;
    }

    /**
     * @param string $systemKey
     * @param string $user
     * @param string $action
     * @param array  $data
     *
     * @return array
     * @throws SystemException
     */
    public function runCustomAction(string $systemKey, string $user, string $action, array $data = []): array
    {
        return $this->manager->runCustomAction($systemKey, $user, $action, $data);
    }

    /**
     * @param string $systemKey
     * @param int    $page
     * @param int    $limit
     *
     * @return array
     * @throws SystemException
     */
    public function getSystemUsers(string $systemKey, int $page, int $limit): array
    {
        $users = $this->manager->getSystemUsers($systemKey, $page, $limit);

        return [
            'count' => count($users),
            'users' => $users,
        ];
    }

    /**
     * @return array
     * @throws SystemException
     */
    public function getSystemCount(): array
    {
        return ['count' => $this->manager->getSystemCount()];
    }

    /**
     * @return array
     * @throws EnumException
     * @throws SystemException
     */
    public function getSystemList(): array
    {
        return $this->manager->getSystemList(TRUE);
    }

    /**
     * @param string $systemKey
     * @param array  $data
     *
     * @return array
     * @throws EnumException
     */
    public function getSystemMetrics(string $systemKey, array $data): array
    {
        $from     = isset($data['from']) ? (string) $data['from'] : NULL;
        $to       = isset($data['to']) ? (string) $data['to'] : NULL;
        $guid     = isset($data['guid']) ? (string) $data['guid'] : NULL;
        $interval = isset($data['interval']) ? (string) $data['interval'] : NULL;

        return $this->manager->getSystemMetrics(
            $systemKey,
            $from ? DateTimeUtils::getUTCDateTime($from) : NULL,
            $to ? DateTimeUtils::getUTCDateTime($to) : NULL,
            $interval,
            $guid
        );
    }

    /**
     * @param string $systemKey
     * @param array  $data
     *
     * @return array
     * @throws EnumException
     */
    public function getSystemRequestCount(string $systemKey, array $data): array
    {
        $from     = isset($data['from']) ? (string) $data['from'] : NULL;
        $to       = isset($data['to']) ? (string) $data['to'] : NULL;
        $guid     = isset($data['guid']) ? (string) $data['guid'] : NULL;
        $interval = isset($data['interval']) ? (string) $data['interval'] : NULL;

        return [
            'count' => $this->manager->getSystemRequestCount(
                $systemKey,
                $from ? DateTimeUtils::getUTCDateTime($from) : DateTimeUtils::getUTCDateTime(),
                $to ? DateTimeUtils::getUTCDateTime($to) : DateTimeUtils::getUTCDateTime(),
                $interval,
                $guid
            ),
        ];
    }

}