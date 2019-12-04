<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Utils\Json;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package Tests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    /**
     * @var Client
     */
    protected static $client;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var NativePasswordEncoder
     */
    protected $encoder;

    /**
     * ControllerTestCaseAbstract constructor.
     *
     * @param null   $name
     * @param array  $data
     * @param string $dataName
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
        self::$client = self::createClient([], []);
        $this->dm     = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getClient()->dropDatabase('pipes-php-sdk');
    }

    /**
     * @param object $document
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /**
     * @param string $url
     *
     * @return object
     */
    protected function sendGet(string $url): object
    {
        self::$client->request('GET', $url);
        /** @var Response $response */
        $response = self::$client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string     $url
     * @param array      $parameters
     * @param array|null $content
     *
     * @return object
     */
    protected function sendPost(string $url, array $parameters, ?array $content = NULL): object
    {
        self::$client->request(
            'POST',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : ''
        );

        /** @var Response $response */
        $response = self::$client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string     $url
     * @param array      $parameters
     * @param array|null $content
     *
     * @return object
     */
    protected function sendPut(string $url, array $parameters, ?array $content = NULL): object
    {
        self::$client->request(
            'PUT',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : ''
        );

        /** @var Response $response */
        $response = self::$client->getResponse();

        return $this->returnResponse($response);
    }

    /**
     * @param string $url
     *
     * @return object
     */
    protected function sendDelete(string $url): object
    {
        self::$client->request('DELETE', $url);

        /** @var Response $response */
        $response = self::$client->getResponse();

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
