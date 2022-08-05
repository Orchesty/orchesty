<?php declare(strict_types=1);

namespace HbPFAppStoreTests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package HbPFAppStoreTests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    /**
     * @var DocumentManager
     */
    protected DocumentManager $dm;

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
     * @var KernelBrowser
     */
    protected static KernelBrowser $client;

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

        $this->encoder = new NativePasswordHasher(3);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupClient();
    }

    /**
     * @throws Exception
     */
    protected function setupClient(): void
    {
        self::$client = self::createClient([], []);

        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getConfiguration()->setDefaultDB($this->getMongoDatabaseName());

        $documents = $this->dm->getMetadataFactory()->getAllMetadata();
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document->getName())->drop();
        }
    }

    /**
     * @param object $document
     *
     * @throws Exception
     */
    protected function persistAndFlush(object $document): void
    {
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /**
     * @param string $url
     *
     * @return object
     * @throws Exception
     */
    protected function sendGet(string $url): object
    {
        self::$client->request('GET', $url);
        $response = self::$client->getResponse();

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
        self::$client->request(
            'POST',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : '',
        );

        $response = self::$client->getResponse();

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
        self::$client->request(
            'PUT',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : '',
        );

        $response = self::$client->getResponse();

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
        self::$client->request('DELETE', $url);

        $response = self::$client->getResponse();

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
            'status'  => $response->getStatusCode(),
            'content' => (object) $content,
        ];
    }

    /**
     * @return string
     */
    private function getMongoDatabaseName(): string
    {
        return sprintf('%s%s', $this->dm->getConfiguration()->getDefaultDB(), getenv('TEST_TOKEN'));
    }

}
