<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Enum\SystemUITypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;

/**
 * Class ShoptetSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet
 */
class ShoptetSystem implements AuthorizationInterface
{

    use SystemTrait;
    use AuthorizationTrait;

    public const URL = 'url';

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
    public function getUIType(): string
    {
        return SystemUITypeEnum::BASIC;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'shoptet';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Shoptet';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shoptet';
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
        return !empty($systemInstall->getSettings()[self::URL] ?? '');
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
        $this->continueOnAuthorized($systemInstall);

        $settings = $systemInstall->getSettings();
        $dto      = new RequestDto($method, new Uri($settings[self::URL]));
        $dto->setHeaders([
            'Content-Type' => 'text/xml',
            'Accept'       => 'text/xml',
        ]);

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $sett = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::URL,
            'Xml feed URL',
            $this->prepareValue(self::URL, $sett),
            TRUE
        );

        $field2 = new Field(
            Field::SELECT,
            SystemInstall::SELECT_LIST,
            'Distribution list',
            $this->prepareValue(SystemInstall::SELECT_LIST, $sett)
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2);

        return $form->toArray();
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

}