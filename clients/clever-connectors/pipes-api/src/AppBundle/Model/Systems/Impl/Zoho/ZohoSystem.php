<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class ZohoSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho
 */
class ZohoSystem implements SystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;

    public const AUTH_TOKEN = 'auth_token';

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
        return 'zoho';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'ZOHO';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'ZOHO system';
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
        return !empty($systemInstall->getSettings()[self::AUTH_TOKEN]);
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
            throw new SystemException('ZOHO system is unauthorize.', SystemException::SYSTEM_IS_UNAUTHORIZED);
        }

        $sett = $systemInstall->getSettings();
        $dto = new RequestDto('GET', new Uri(
            sprintf('https://crm.zoho.eu/crm/private/json/Contacts/%%s?authtoken=%s&scope=crmapi', $sett[self::AUTH_TOKEN])
        ));
        $dto->setHeaders($this->getHeaders());

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
            self::AUTH_TOKEN,
            'Authorization token',
            $this->prepareValue(self::AUTH_TOKEN, $systemInstall->getSettings()),
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
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

}