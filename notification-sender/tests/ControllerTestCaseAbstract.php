<?php declare(strict_types=1);

namespace Tests;

use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package Tests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use TestCaseTrait;
    use ControllerTestTrait;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareClient();
        $this->prepareDatabase();
    }

    /**
     * @param string  $url
     * @param mixed[] $parameters
     * @param mixed[] $headers
     *
     * @return ControllerResponse
     */
    protected function sendGet(string $url, array $parameters = [], array $headers = []): ControllerResponse
    {
        $this->prepareClient();

        $this->client->request('GET', $url, $parameters, [], $headers);
        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param string  $url
     * @param mixed[] $parameters
     * @param mixed[] $headers
     * @param string  $content
     * @param mixed[] $files
     *
     * @return ControllerResponse
     */
    protected function sendPost(
        string $url,
        array $parameters = [],
        array $headers = [],
        string $content = '',
        array $files = []
    ): ControllerResponse
    {
        $this->prepareClient();

        $this->client->request('POST', $url, $parameters, $files, $headers, $content);
        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param string  $url
     * @param mixed[] $parameters
     * @param mixed[] $headers
     * @param mixed[] $files
     *
     * @return ControllerResponse
     */
    protected function sendPut(
        string $url,
        array $parameters = [],
        array $headers = [],
        array $files = []
    ): ControllerResponse
    {
        $this->prepareClient();

        $this->client->request('PUT', $url, $parameters, $files, $headers);
        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param string  $url
     * @param mixed[] $headers
     *
     * @return ControllerResponse
     */
    protected function sendDelete(string $url, array $headers = []): ControllerResponse
    {
        $this->prepareClient();

        $this->client->request('DELETE', $url, [], [], $headers);
        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param ControllerResponse $response
     * @param int                $status
     * @param mixed[]            $content
     */
    protected function assertResponse(ControllerResponse $response, int $status = 200, array $content = []): void
    {
        $responseStatus  = $response->getStatus();
        $responseContent = $response->getContent();

        if ($responseStatus !== $status) {
            $message = sprintf('%s%s', Json::encode($responseContent), PHP_EOL);
        }

        self::assertEquals($status, $responseStatus, $message ?? '');

        if ($content) {
            self::assertEquals($content, $responseContent);
        }
    }

    /**
     * @param Response $response
     *
     * @return ControllerResponse
     */
    protected function processResponse(Response $response): ControllerResponse
    {
        try {
            return new ControllerResponse($response->getStatusCode(), Json::decode((string) $response->getContent()));
        } catch (Throwable $e) {
            return new ControllerResponse($response->getStatusCode(), [$response->getContent()]);
        }
    }

    /**
     *
     */
    private function prepareClient(): void
    {
        $this->startClient();
    }

}
