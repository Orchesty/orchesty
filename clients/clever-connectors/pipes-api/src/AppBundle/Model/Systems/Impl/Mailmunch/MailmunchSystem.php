<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class MailmunchSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch
 */
class MailmunchSystem implements SystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;

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
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
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
    public function getKey(): string
    {
        return 'mailmunch';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Mailmunch';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Mailmunch system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        return new RequestDto('', new Uri());
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

}