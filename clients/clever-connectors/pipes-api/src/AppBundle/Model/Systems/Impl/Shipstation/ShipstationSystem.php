<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class ShipstationSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation
 */
class ShipstationSystem implements AuthorizationInterface
{

    use AuthorizationTrait;

    private const SYSTEM_URL = 'https://ssapi.shipstation.com';
    private const API_KEY    = 'api_key';
    private const API_SECRET = 'api_secret';

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $settings = $systemInstall->getSettings();

        return !empty($settings[self::API_KEY] ?? '') && !empty($settings[self::API_SECRET] ?? '');
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
        return 'shipstation';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Shipstation';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shipstation description...';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
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

        $settings      = $systemInstall->getSettings();
        $authorization = sprintf('%s:%s', $settings[self::API_KEY], $settings[self::API_SECRET]);

        return (new RequestDto($method, new Uri(self::SYSTEM_URL)))
            ->setHeaders([
                'Authorization' => sprintf('Basic %s', base64_encode($authorization)),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);
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
            self::API_KEY,
            'API Key',
            $this->prepareValue(self::API_KEY, $settings),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            self::API_SECRET,
            'API Secret',
            $this->prepareValue(self::API_SECRET, $settings),
            TRUE
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2);

        return $form->toArray();
    }

}