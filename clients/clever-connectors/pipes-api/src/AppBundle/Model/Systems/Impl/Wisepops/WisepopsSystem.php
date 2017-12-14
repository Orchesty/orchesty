<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Requester\WisepopsSubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Requester\WisepopsUnsubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;

/**
 * Class WisepopsSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops
 */
class WisepopsSystem implements WebhookSystemInterface, AuthorizationInterface
{

    use SystemTrait {
        toArray as parentToArray;
    }
    use AuthorizationTrait;
    use WebhookSystemTrait;

    public const   FORM_ID     = 'form_id';
    public const   FORM_LIST   = 'list';
    public const   WEBHOOK_URL = 'https://app.wisepops.com/api1/hooks';

    private const  API_KEY   = 'api_key';
    private const  FORM_NAME = 'form_name';
    private const  BASE_URL  = 'https://app.wisepops.com/';
    private const  INFO_URL  = 'https://app.wisepops.com/api1/wisepops';

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * WisepopsSystem constructor.
     *
     * @param CurlManagerInterface $curlManager
     * @param DocumentManager      $dm
     */
    public function __construct(CurlManagerInterface $curlManager, DocumentManager $dm)
    {
        $this->subscriptions[] = new WebhookSubscribes(
            'wisepops-created-email-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS, $this->getKey())
        );
        $this->curlManager     = $curlManager;
        $this->dm              = $dm;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::WEBHOOK;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'wisepops';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'WisePOPS';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'WisePOPS.';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'WisePOPS logo';
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        return !empty($systemInstall->getSettings()[self::API_KEY] ?? '');
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

        $dto = new RequestDto($method, new Uri(self::BASE_URL));
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
        $field1 = new Field(
            Field::TEXT,
            self::API_KEY,
            'Api key.',
            $this->prepareValue(self::API_KEY, $systemInstall->getSettings()),
            TRUE
        );

        $form = new Form();
        $form->addField($field1);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     * @throws SystemException
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new WisepopsSubscribeRequester($this->getHeaders($systemInstall));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     * @throws SystemException
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new WisepopsUnsubscribeRequester($this->getHeaders($systemInstall));
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return array
     */
    public function refreshForms(SystemInstall $systemInstall, array $data = []): array
    {
        $dto = $this->getRequestDto($systemInstall, 'GET');
        $dto->setUri(new Uri(self::INFO_URL));

        $res   = $this->curlManager->send($dto);
        $forms = json_decode($res->getBody(), TRUE);

        $sForms = [];

        $sett = $systemInstall->getSettings();
        if (array_key_exists(SystemInstall::FORMS, $sett)) {
            $sForms = $sett[SystemInstall::FORMS];

            foreach ($sForms as $form) {
                $this->removeForm($forms, $form[self::FORM_ID]);
            }
        }

        foreach ($forms as $form) {
            $sForms[] = [
                self::FORM_ID   => $form['id'],
                self::FORM_NAME => $form['label'],
                self::FORM_LIST => NULL,
            ];
        }

        $sett[SystemInstall::FORMS] = $sForms;
        $systemInstall->setSettings($sett);
        $this->dm->flush();

        return $sForms;
    }

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return array
     */
    public function toArray(?SystemInstall $systemInstall = NULL): array
    {
        $arr = $this->parentToArray($systemInstall);
        if (array_key_exists(SystemInstall::FORMS, $systemInstall->getSettings())) {
            $arr[SystemInstall::FORMS] = $systemInstall->getSettings()[SystemInstall::FORMS];
        }

        return $arr;
    }

    /**
     * ------------------------------------------------ HELPERS ------------------------------------------------
     */

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => sprintf('WISEPOPS-API key="%s"', $systemInstall->getSettings()[self::API_KEY]),
        ];
    }

    /**
     * @param array      $array
     * @param int|string $id
     */
    private function removeForm(array &$array, $id): void
    {
        foreach ($array as $index => $item) {
            if ($id === $item['id']) {
                unset($array[$index]);
                break;
            }
        }
    }

}