<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class AirtableSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable
 */
class AirtableSystem implements AuthorizationInterface
{

    use SystemTrait;
    use AuthorizationTrait;

    public const  BASE_URL = 'https://api.airtable.com/v0/';
    private const API_KEY  = 'api_key';
    private const VIEW     = 'view';
    private const URL      = 'url';

    /**
     * AirtableSystem constructor.
     */
    public function __construct()
    {
        $topologyName = TopologyNameUtils::getTopologyName(TopologyNameUtils::SYNC, $this->getKey());
        $this->addAllowedAction(new ActionDto($topologyName, MapTemplate::DIRECTION_IN));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $settings = $systemInstall->getSettings();

        return !empty($settings[self::API_KEY] ?? '') && !empty($settings[self::URL] ?? '');
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
        return 'airtable';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Airtable';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Airtable system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

    /**
     * @return bool
     */
    public function isDynamicMapper(): bool
    {
        return TRUE;
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

        $settings = $systemInstall->getSettings();

        $uri = $settings[self::URL];
        if (strpos($uri, '?')) {
            $tmp = explode('?', $uri);
            $uri = $tmp[0];
        }

        // use view if set
        if ($settings[self::VIEW] ?? '') {
            $uri .= sprintf('?view=%s', $settings[self::VIEW]);
        }

        return (new RequestDto($method, new Uri($uri)))
            ->setHeaders([
                'Authorization' => sprintf('Bearer %s', $settings[self::API_KEY]),
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
            self::URL,
            'Url of the table',
            $this->prepareValue(self::URL, $settings),
            TRUE
        );

        $field3 = new Field(
            Field::TEXT,
            self::VIEW,
            'View',
            $this->prepareValue(self::URL, $settings)
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        return $form->toArray();
    }

}