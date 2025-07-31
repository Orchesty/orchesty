<?php declare(strict_types=1);

namespace PipesPhpSdkTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\RestoreErrorHandlersTrait;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package PipesPhpSdkTests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use ControllerTestTrait;
    use CustomAssertTrait;
    use RestoreErrorHandlersTrait;

    /**
     * @var Session<mixed>
     */
    protected Session $session;

    /**
     * @var TokenStorage
     */
    protected TokenStorage $tokenStorage;

    /**
     * @var NativePasswordHasher
     */
    protected NativePasswordHasher $encoder;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->encoder = new NativePasswordHasher(3);

        $this->startClient();
    }

    /**
     * @param string $url
     *
     * @return object
     * @throws Exception
     */
    protected function sendGet(string $url): object
    {
        $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string       $url
     * @param mixed[]      $parameters
     * @param mixed[]|null $content
     *
     * @return object
     * @throws Exception
     */
    protected function sendPost(string $url, array $parameters, ?array $content = NULL): object
    {
        $this->client->request(
            'POST',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : '',
        );

        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string       $url
     * @param mixed[]      $parameters
     * @param mixed[]|null $content
     *
     * @return object
     * @throws Exception
     */
    protected function sendPut(string $url, array $parameters, ?array $content = NULL): object
    {
        $this->client->request(
            'PUT',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : '',
        );

        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string $url
     *
     * @return object
     * @throws Exception
     */
    protected function sendDelete(string $url): object
    {
        $this->client->request('DELETE', $url);

        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param Response $response
     *
     * @return object
     * @throws Exception
     */
    protected function returnResponse(Response $response): object
    {
        $content = Json::decode((string) $response->getContent());
        if (isset($content['error_code'])) {
            $content['errorCode'] = $content['error_code'];
            unset($content['error_code']);
        }

        return (object) [
            'content' => (object) $content,
            'status'  => $response->getStatusCode(),
        ];
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();

        $this->restoreErrorHandler();
        $this->restoreExceptionHandler();
    }

}
