<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Systems\WebhookSystemInterface;
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
     * @var WebhookSubscribes[]
     */
    private $subs;

    /**
     * NullSystem constructor.
     */
    function __construct()
    {
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

}