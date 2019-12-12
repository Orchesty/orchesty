<?php declare(strict_types=1);

namespace Tests;

use Exception;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package Tests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use ControllerTestTrait;
    use DatabaseTestTrait;

    /**
     * @var NativePasswordEncoder
     */
    protected $encoder;

    /**
     * ControllerTestCaseAbstract constructor.
     *
     * @param null    $name
     * @param mixed[] $data
     * @param string  $dataName
     */
    public function __construct($name = NULL, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->encoder = new NativePasswordEncoder(3);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->startClient();
        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
    }

    /**
     * @param string $url
     *
     * @return object
     */
    protected function sendGet(string $url): object
    {
        $this->client->request('GET', $url);
        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string       $url
     * @param mixed[]      $parameters
     * @param mixed[]|null $content
     *
     * @return object
     */
    protected function sendPost(string $url, array $parameters, ?array $content = NULL): object
    {
        $this->client->request(
            'POST',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : ''
        );

        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string       $url
     * @param mixed[]      $parameters
     * @param mixed[]|null $content
     *
     * @return object
     */
    protected function sendPut(string $url, array $parameters, ?array $content = NULL): object
    {
        $this->client->request(
            'PUT',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : ''
        );

        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string $url
     *
     * @return object
     */
    protected function sendDelete(string $url): object
    {
        $this->client->request('DELETE', $url);

        /** @var Response $response */
        $response = $this->client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param Response $response
     *
     * @return object
     */
    protected function returnResponse(Response $response): object
    {
        $content = Json::decode((string) $response->getContent());
        if (isset($content['error_code'])) {
            $content['errorCode'] = $content['error_code'];
            unset($content['error_code']);
        }

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => (object) $content,
        ];
    }

}
