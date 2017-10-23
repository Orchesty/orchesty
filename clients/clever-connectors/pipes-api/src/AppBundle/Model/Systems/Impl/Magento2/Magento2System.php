<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Magento2;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Redirect\Redirect;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;

/**
 * Class Magento2System
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Magento2
 */
class Magento2System implements OAuth2Interface
{

    use AuthorizationTrait;

    private const USERNAME   = 'user_name';
    private const SYSTEM_URL = 'system_url';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CurlManagerInterface
     */
    private $curl;

    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Magento2System constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curl
     * @param Redirect             $redirect
     */
    function __construct(DocumentManager $dm, CurlManagerInterface $curl, Redirect $redirect)
    {
        $this->dm       = $dm;
        $this->curl     = $curl;
        $this->redirect = $redirect;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $sett = $systemInstall->getSettings();
        if (empty($sett[self::USERNAME] ?? '') || empty($sett[self::PASSWORD] ?? '') || empty($sett[self::SYSTEM_URL] ?? '')) {
            return FALSE;
        }

        if (empty($systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN] ?? '')) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::OAUTH2;
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
        return 'magento2';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Magento2 system';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Magento2 description...';
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
     */
    public function authorize(SystemInstall $systemInstall): void
    {
        $res   = $this->curl->send($this->getDto($systemInstall));
        $token = $res->getBody();

        $this->saveToken($systemInstall, [$token]);
        //@TODO make better
        //$this->redirect->make($systemInstall->getSettings()[self::FRONTEND_REDIRECT_URL]);
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
        $this->continueOnAuthorized($systemInstall);

        $sett = $systemInstall->getSettings();
        $dto  = new RequestDto($method, new Uri($sett[self::SYSTEM_URL]));
        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $settings = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::USERNAME,
            'Username',
            $this->prepareValue(self::USERNAME, $settings),
            TRUE
        );

        $field2 = new Field(
            Field::PASSWORD,
            self::PASSWORD,
            'Password.',
            $this->prepareValue(self::PASSWORD, $settings),
            TRUE
        );

        $field3 = new Field(
            Field::URL,
            self::SYSTEM_URL,
            'System url clients magento2 instance.',
            $this->prepareValue(self::SYSTEM_URL, $settings),
            TRUE
        );

        $form = (new Form())
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall
    {
        $this->setSettings($systemInstall, [OAuth2Provider::ACCESS_TOKEN => trim($data[0], '"')]);
        $systemInstall->setExpires(new DateTime('+1 hours'));
        $this->dm->flush();

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInstall
     */
    public function refreshToken(SystemInstall $systemInstall): SystemInstall
    {
        $this->authorize($systemInstall);

        return $systemInstall;
    }

    /**
     * ---------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        return [
            'Authorization' => 'Bearer ' . $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN],
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequestDto
     */
    private function getDto(SystemInstall $systemInstall): RequestDto
    {
        $sett = $systemInstall->getSettings();
        $dto  = new RequestDto('POST',
            new Uri(sprintf('%s/rest/V1/integration/admin/token', $sett[self::SYSTEM_URL])));
        $dto->setBody(json_encode(
            [
                'username' => $sett[self::USERNAME],
                'password' => $sett[self::PASSWORD],
            ]
        ));
        $dto->setHeaders([
            'Content-Type' => 'application/json',
        ]);

        return $dto;
    }

}