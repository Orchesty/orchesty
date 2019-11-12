<?php declare(strict_types=1);

namespace Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
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

    /**
     * @var Client
     */
    protected static $client;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->prepareDatabase();
        $this->prepareClient();
    }

    /**
     * @param string $url
     * @param array  $parameters
     * @param array  $headers
     *
     * @return ControllerResponse
     */
    protected function sendGet(string $url, array $parameters = [], array $headers = []): ControllerResponse
    {
        $this->prepareClient();

        self::$client->request('GET', $url, $parameters, [], $headers);
        /** @var Response $response */
        $response = self::$client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param string $url
     * @param array  $parameters
     * @param array  $headers
     * @param string $content
     * @param array  $files
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

        self::$client->request('POST', $url, $parameters, $files, $headers, $content);
        /** @var Response $response */
        $response = self::$client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param string $url
     * @param array  $parameters
     * @param array  $headers
     * @param array  $files
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

        self::$client->request('PUT', $url, $parameters, $files, $headers);
        /** @var Response $response */
        $response = self::$client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param string $url
     * @param array  $headers
     *
     * @return ControllerResponse
     */
    protected function sendDelete(string $url, array $headers = []): ControllerResponse
    {
        $this->prepareClient();

        self::$client->request('DELETE', $url, [], [], $headers);
        /** @var Response $response */
        $response = self::$client->getResponse();

        return $this->processResponse($response);
    }

    /**
     * @param ControllerResponse $response
     * @param int                $status
     * @param array              $content
     */
    protected function assertResponse(ControllerResponse $response, int $status = 200, array $content = []): void
    {
        $responseStatus  = $response->getStatus();
        $responseContent = $response->getContent();

        if ($responseStatus !== $status) {
            $message = sprintf('%s%s', json_encode($responseContent, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL);
        }

        $this->assertEquals($status, $responseStatus, $message ?? '');

        if ($content) {
            $this->assertEquals($content, $responseContent);
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
            $content      = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
            $innerContent = [];

            if (!is_array($content)) {
                $innerContent['message'] = $content;
            }
        } catch (Throwable $e) {
            return new ControllerResponse($response->getStatusCode(), [$response->getContent()]);
        }

        return new ControllerResponse($response->getStatusCode(), $innerContent ?: $content);
    }

    /**
     *
     */
    private function prepareClient(): void
    {
        self::$client    = self::createClient();
        self::$container = self::$client->getContainer() ?? self::$container;
        self::$container->set('doctrine_mongodb.odm.default_document_manager', $this->dm);
    }

}
