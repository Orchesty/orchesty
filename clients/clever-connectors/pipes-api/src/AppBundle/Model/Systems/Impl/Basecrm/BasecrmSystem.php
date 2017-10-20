<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;

/**
 * Class BasecrmSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm
 */
class BasecrmSystem implements SystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;

    public const QUE_ID = 'que_id';

    private const SYNC_UUID    = 'sync_uuid';
    private const ACCESS_TOKEN = 'access_token';
    private const SYSTEM_URL   = 'https://api.getbase.com/';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * BasecrmSystem constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     */
    function __construct(DocumentManager $dm, CurlManagerInterface $curl)
    {
        $this->dm   = $dm;
        $this->curl = $curl;
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
        return 'BaseCRM system';
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

        if (empty($sett[self::ACCESS_TOKEN] ?? '')) {
            return FALSE;
        } else if (empty($sett[self::QUE_ID] ?? '')
            || empty($sett[self::SYNC_UUID] ?? '')
        ) {
            return $this->createSyncQue($systemInstall);
        };

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
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException('BaseCRM is unauthorized.');
        }

        $dto = new RequestDto($method, new Uri(self::SYSTEM_URL));
        $dto->setHeaders($this->getHeaders($systemInstall, NULL));

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
     * --------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     * @param null|string   $uuid
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall, ?string $uuid = NULL): array
    {
        if (!$uuid) {
            $uuid = $systemInstall->getSettings()[self::SYNC_UUID];
        }

        return [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'User-Agent'    => $_SERVER['HTTP_USER_AGENT'],
            'Authorization' => 'Bearer ' . $systemInstall->getSettings()[self::ACCESS_TOKEN],
            'X-Basecrm-Device-UUID' => $uuid,
        ];
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     * @throws SystemException
     */
    private function createSyncQue(SystemInstall $systemInstall): bool
    {
        $uuid = uniqid();
        $dto  = new RequestDto('POST', new Uri(sprintf('%s/v2/sync/start', rtrim(self::SYSTEM_URL, '/'))));
        $dto->setHeaders($this->getHeaders($systemInstall, $uuid));

        $res = $this->curl->send($dto);
        if (!in_array($res->getStatusCode(), [201, 204])) {
            throw new SystemException(sprintf('BaseCRM failed to create sync que, %s', $res->getBody()),
                SystemException::MISSING_RESPONSE_DATA);
        }

        $body = json_decode($res->getBody(), TRUE);
        if (!array_key_exists('data', $body)
            || !array_key_exists('id', $body['data'])
        ) {
            throw new SystemException(sprintf('BaseCRM failed to create sync que (missing id), %s', $res->getBody()),
                SystemException::MISSING_RESPONSE_DATA);
        }

        $sett                           = $systemInstall->getSettings();
        $sett[BasecrmSystem::SYNC_UUID] = $uuid;
        $sett[BasecrmSystem::QUE_ID]    = $body['data']['id'];

        $systemInstall->setSettings($sett);
        $this->dm->flush();

        return TRUE;
    }

}