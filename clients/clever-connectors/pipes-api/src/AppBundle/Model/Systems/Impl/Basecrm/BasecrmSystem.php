<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class BasecrmSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm
 */
class BasecrmSystem implements AuthorizationInterface, CMEventSystemInterface
{

    use AuthorizationTrait;
    use CMEventSystemTrait;

    public const QUE_ID     = 'que_id';
    public const SYNC_UUID  = 'sync_uuid';
    public const SYSTEM_URL = 'https://api.getbase.com/';

    private const ACCESS_TOKEN = 'access_token';

    /**
     * BasecrmSystem constructor.
     */
    function __construct()
    {
        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
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
        return 'basecrm';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'BaseCRM';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'BaseCRM';
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
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $sett = $systemInstall->getSettings();

        return !empty($sett[self::ACCESS_TOKEN] ?? '');
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
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $dto = new RequestDto($method, new Uri(self::SYSTEM_URL));
        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDtoNonSync(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $dto = new RequestDto($method, new Uri(self::SYSTEM_URL));
        $dto->setHeaders($this->getHeaders($systemInstall, FALSE));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $field1 = new Field(
            Field::TEXT,
            self::ACCESS_TOKEN,
            'Access token',
            $this->prepareValue(self::ACCESS_TOKEN, $systemInstall->getSettings()),
            TRUE
        );

        $form = new Form();
        $form->addField($field1);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     * @param bool          $sync
     *
     * @return array
     */
    public function getHeaders(SystemInstall $systemInstall, bool $sync = TRUE): array
    {
        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'User-Agent'    => 'Chrome/58.0.3029.96 Safari/537.36',
            'Authorization' => 'Bearer ' . $systemInstall->getSettings()[self::ACCESS_TOKEN],
        ];

        if ($sync) {
            $uuid                             = $systemInstall->getSettings()[self::SYNC_UUID];
            $headers['X-Basecrm-Device-UUID'] = $uuid;
        }

        return $headers;
    }

    /**
     * @return RequesterInterface
     */
    public function getCMEventRequester(): ?RequesterInterface
    {
        return NULL;
    }

}