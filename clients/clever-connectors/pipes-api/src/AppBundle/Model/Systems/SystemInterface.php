<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Interface SystemInterface
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
interface SystemInterface
{

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return string
     */
    public function getLogo(): string;

    /**
     * @return bool
     */
    public function isDynamicMapper(): bool;

    /**
     * @return array
     */
    public function getAllowedActions(): array;

    /**
     * @return array
     */
    public function getAllowedActionsArray(): array;

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto;

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array;

    /**
     * @param string $name
     *
     * @return string
     */
    public function getCustomTopologyName(string $name): string;

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array;

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto;

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall;

}