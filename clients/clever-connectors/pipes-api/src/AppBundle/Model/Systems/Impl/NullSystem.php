<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Systems\WebhookSystemInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class NullSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl
 */
class NullSystem implements WebhookSystemInterface
{

    public const URL      = 'url';
    public const USERNAME = 'username';
    public const PASSWORD = 'password';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var WebhookSubscribes[]
     */
    private $subs;

    /**
     * @var string
     */
    private $user;

    /**
     * NullSystem constructor.
     *
     * @param DocumentManager $dm
     */
    function __construct(DocumentManager $dm)
    {
        $this->dm     = $dm;
        $this->subs[] = new WebhookSubscribes('node', 'top', 'uriReg', 'uriUnreg');
    }

    /**
     * @param string $user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
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
        return 'null';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'NULL';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Only for testing purposes';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

    /**
     * @return array
     */
    public function getWebhookSubscribes(): array
    {
        return $this->subs;
    }

    /**
     * @param string $url
     *
     * @return RequestDto
     */
    public function getSubscribeRequest(string $url): RequestDto
    {
        return new RequestDto('POST', new Uri('uriSub'));
    }

    /**
     * @param string $id
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(string $id): RequestDto
    {
        return new RequestDto('POST', new Uri('uriUnsub'));
    }

    /**
     * @param ResponseDto $response
     *
     * @return string
     */
    public function getWebhookId(ResponseDto $response): string
    {
        return '9';
    }

    /**
     * @param WebhookSubscribes $sub
     *
     * @return WebhookSystemInterface
     */
    public function addWebhookSubscribes(WebhookSubscribes $sub): WebhookSystemInterface
    {
        $this->subs[] = $sub;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'        => $this->getType(),
            'key'         => $this->getKey(),
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
            'authType'    => $this->getAuthorizationType(),
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        $systemInstall = $this->getSystemInstall();
        if ($systemInstall && $systemInstall->getSettings()) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return array
     */
    public function getHeaders(string $method, string $url): array
    {
        return [];
    }

    /**
     * @param string $hostname
     *
     * @return string []
     */
    public function getInfo(string $hostname): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        $systemInstall = $this->getSystemInstall();
        if ($systemInstall) {
            $form = $this->getForm($systemInstall->getSettings());

            return $form->toArray();
        }

        return [];
    }

    /**
     * @param string[] $data
     */
    public function saveSettings(array $data): void
    {
        count($data);
    }

    /**
     * @return string
     */
    public function getReadMe(): string
    {
        return '';
    }

    /**
     * @return SystemInstall|null
     */
    private function getSystemInstall(): ?SystemInstall
    {
        return $this->dm->getRepository(SystemInstall::class)->findOneBy([
            'user'   => $this->user,
            'system' => $this->getKey(),
        ]);
    }

    /**
     * @param array $settings
     *
     * @return Form
     */
    private function getForm(array $settings): Form
    {
        $field1 = new Field(Field::URL, self::URL, $this->prepareValue(self::URL, $settings), TRUE);
        $field2 = new Field(Field::TEXT, self::USERNAME, $this->prepareValue(self::USERNAME, $settings), TRUE);
        $field3 = new Field(Field::PASSWORD, self::PASSWORD, $this->prepareValue(self::PASSWORD, $settings), TRUE);

        $form = (new Form())
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        return $form;
    }

    /**
     * @param string $key
     * @param array  $settings
     *
     * @return bool|mixed|null
     */
    private function prepareValue(string $key, array $settings)
    {
        if (isset($settings[$key])) {
            if ($key == self::PASSWORD) {
                return empty($settings[$key]) ? FALSE : TRUE;
            }

            return $settings[$key];
        }

        return NULL;
    }

}