<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class TestSystem
 *
 * @package Tests\Unit\AppBundle\Model\Systems
 */
final class TestSystem implements AuthorizationInterface
{

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     */
    public function customAction(SystemInstall $systemInstall, array $data = []): array
    {
        return $data;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function setSettings(SystemInstall $systemInstall, array $data): SystemInstall
    {
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $password
     *
     * @return SystemInstall
     */
    public function setPassword(SystemInstall $systemInstall, string $password): SystemInstall
    {
    }

    /**
     * @return string
     */
    public function getType(): string
    {
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
    }

    /**
     * @return bool
     */
    public function isDynamicMapper(): bool
    {
    }

    /**
     * @return array
     */
    public function getAllowedActions(): array
    {
    }

    /**
     * @return array
     */
    public function getAllowedActionsArray(): array
    {
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getCustomTopologyName(string $name): string
    {
    }

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array
    {
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall
    {
    }

}
