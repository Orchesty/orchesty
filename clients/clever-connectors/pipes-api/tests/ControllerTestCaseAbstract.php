<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Nette\Utils\Json;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package Tests
 */
class ControllerTestCaseAbstract extends WebTestCase
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * DatabaseTestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();
        self::bootKernel();
        $this->container = self::$kernel->getContainer();
        $this->dm        = $this->container->get('doctrine_mongodb.odm.default_document_manager');
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient([], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->dm->getConnection()->dropDatabase('clever-connectors');
    }

    /**
     * @param object $document
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush($document);
    }

    /**
     * @param string     $url
     * @param array|null $parameters
     *
     * @return stdClass
     */
    protected function sendGet(string $url, ?array $parameters = []): stdClass
    {
        $this->client->request('GET', $url, $parameters);
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];
    }

    /**
     * @param string      $url
     * @param array|null  $parameters
     * @param string|null $content
     *
     * @return stdClass
     */
    protected function sendPost(string $url, ?array $parameters = [], ?string $content = NULL): stdClass
    {
        $this->client->request('POST', $url, $parameters, [], [], $content);
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];
    }

    /**
     * @param string      $url
     * @param array|null  $parameters
     * @param string|null $content
     *
     * @return stdClass
     */
    protected function sendPut(string $url, ?array $parameters = [], ?string $content = NULL): stdClass
    {
        $this->client->request('PUT', $url, $parameters, [], [], $content);
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];
    }

    /**
     * @param string $url
     *
     * @return stdClass
     */
    protected function sendDelete(string $url): stdClass
    {
        $this->client->request('DELETE', $url);
        $response = $this->client->getResponse();

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => Json::decode($response->getContent()),
        ];
    }

}