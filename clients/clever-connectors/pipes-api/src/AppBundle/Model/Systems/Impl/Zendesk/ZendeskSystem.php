<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class ZendeskSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk
 */
class ZendeskSystem implements SystemInterface, AuthorizationInterface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;

    private const DOMAIN    = 'domain';
    private const USER      = 'user_email';
    private const API_TOKEN = 'api_token';

    private const BASE_URL = 'https://%s.zendesk.com/';

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
        return 'zendesk';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Zendesk';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Zendesk system';
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
        return !empty($systemInstall->getSettings()[self::API_TOKEN] ?? '')
            && !empty($systemInstall->getSettings()[self::USER] ?? '')
            && !empty($systemInstall->getSettings()[self::DOMAIN] ?? '');
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
        $sett = $systemInstall->getSettings();
        $dto  = new RequestDto('GET', new Uri(sprintf(self::BASE_URL, $sett[self::DOMAIN])));
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
        $sett = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::USER,
            'User email',
            $this->prepareValue(self::USER, $sett),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            self::API_TOKEN,
            'Api token',
            $this->prepareValue(self::API_TOKEN, $sett),
            TRUE
        );

        $field3 = new Field(
            Field::TEXT,
            self::DOMAIN,
            'Domain xxx.zendesk.com (only xxx part)',
            $this->prepareValue(self::DOMAIN, $sett),
            TRUE
        );

        $form = new Form();
        $form->addField($field1)
            ->addField($field2)
            ->addField($field3);

        return $form->toArray();
    }

    /**
     * -------------------------------------------- HELPERS --------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        $sett  = $systemInstall->getSettings();
        $token = base64_encode(sprintf('%s/token:%s', $sett[self::USER], $sett[self::API_TOKEN]));

        return [
            'Content-Type'  => 'application/json',
            'Authorization' => sprintf('Basic %s', $token),
        ];
    }

}