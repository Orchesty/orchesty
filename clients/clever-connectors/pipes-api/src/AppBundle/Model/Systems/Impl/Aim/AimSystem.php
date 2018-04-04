<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AimSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim
 */
final class AimSystem implements AuthorizationInterface
{

    public const HEADER_ACTION      = 'action';
    public const HEADER_DESTINATION = 'destination';

    public const SYNC_ACTION = 'sync';
    public const SYNC_TOPO   = 'aim-sync';
    public const SYNC_NODE   = 'signal-event';

    public const DELETE_ACTION = 'delete';
    public const DELETE_TOPO   = 'aim-delete';
    public const DELETE_NODE   = 'signal-event';

    public const DESTINATION_ALL     = 'SYNCHRONIZATION_LOCATION_ALL';
    public const DESTINATION_AMERICA = 'SYNCHRONIZATION_LOCATION_AMERICA';
    public const DESTINATION_ASIA    = 'SYNCHRONIZATION_LOCATION_ASIA';
    public const DESTINATION_EUROPE  = 'SYNCHRONIZATION_LOCATION_EUROPE';

    /**
     * @var array
     */
    public $validDestinations = [
        self::DESTINATION_ALL,
        self::DESTINATION_AMERICA,
        self::DESTINATION_ASIA,
        self::DESTINATION_EUROPE,
    ];

    /**
     * @var StartingPointHandler
     */
    private $startingPoint;

    /**
     * @param StartingPointHandler $startingPoint
     */
    public function __construct(StartingPointHandler $startingPoint)
    {
        $this->startingPoint = $startingPoint;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function runSync(array $data): array
    {
        $request = $this->createRequest($data);
        $request->headers->set(CMHeaders::createKey(self::HEADER_ACTION), self::SYNC_ACTION);
        $request->headers->set(CMHeaders::createKey(self::HEADER_DESTINATION), $this->getDestination($data));

        $this->startingPoint->runWithRequest($request, self::SYNC_TOPO, self::SYNC_NODE);

        return [];

    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    public function runDelete(array $data): bool
    {
        $request = $this->createRequest($data);
        $request->headers->set(CMHeaders::createKey(self::HEADER_ACTION), self::DELETE_ACTION);
        $request->headers->set(CMHeaders::createKey(self::HEADER_DESTINATION), $this->getDestination($data));

        $this->startingPoint->runWithRequest($request, self::DELETE_TOPO, self::DELETE_NODE);

        return TRUE;

    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'aim';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'AIM';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'AIM system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        return TRUE;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function setSettings(SystemInstall $systemInstall, array $data): SystemInstall
    {
        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $password
     *
     * @return SystemInstall
     */
    public function setPassword(SystemInstall $systemInstall, string $password): SystemInstall
    {
        return $systemInstall;
    }

    /**
     * @return bool
     */
    public function isDynamicMapper(): bool
    {
        return FALSE;
    }

    /**
     * @return array
     */
    public function getAllowedActions(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAllowedActionsArray(): array
    {
        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     * @throws \Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        return new RequestDto(CurlManager::METHOD_GET, new Uri(''));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        return [];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getCustomTopologyName(string $name): string
    {
        return '';
    }

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array
    {
        return [];
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        return NULL;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall
    {
        return $systemInstall;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws CleverConnectorsException
     */
    private function getDestination(array $data): string
    {
        if (!array_key_exists('destinations', $data) || count($data['destinations']) === 0) {
            throw new CleverConnectorsException('Missing destination.', CleverConnectorsException::MISSING_DATA);
        }

        foreach ($data['destinations'] as $destination) {
            if (!in_array($destination, $this->validDestinations)) {
                throw new CleverConnectorsException(
                    sprintf('Invalid destination "%s"', $destination),
                    CleverConnectorsException::INVALID_DATA
                );
            }
        }

        return implode(',', $data['destinations']);
    }

    /**
     * @param array $data
     *
     * @return Request
     */
    private function createRequest(array $data): Request
    {
        return new Request([], [], [], [], [], [], json_encode($data));
    }

}
