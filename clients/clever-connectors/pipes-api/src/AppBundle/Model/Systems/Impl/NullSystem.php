<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
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

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var WebhookSubscribes[]
     */
    private $subs;

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
            'system' => $this->getKey(),
        ]);
    }

}