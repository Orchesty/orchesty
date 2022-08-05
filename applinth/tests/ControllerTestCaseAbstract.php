<?php declare(strict_types=1);

namespace ApplinthTests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\Applinth\Manager\AuthorizationManager;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\String\Json;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A128GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
use Jose\Component\KeyManagement\JWKFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UserBundleTests\JwtUserTrait;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package ApplinthTests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use ControllerTestTrait;
    use DatabaseTestTrait;
    use JwtUserTrait;
    use LoginJwtTestTrait;
    use PrivateTrait;

    /**
     * @var DocumentManager
     */
    protected DocumentManager $dm;

    /**
     * @var User
     */
    protected User $user;

    /**
     * @var string
     */
    protected string $jwt = '';

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        putenv('METRICS_ODM_DSN=mongodb://mongo');
        $this->setupClient();

        // Login
        [$this->user, $this->jwt] = $this->loginUser('test@example.com', 'password');
    }

    /**
     * @throws Exception
     */
    protected function setupClient(): void
    {
        $this->client = self::createClient([], []);

        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getConfiguration()->setDefaultDB($this->getMongoDatabaseName());

        $documents = $this->dm->getMetadataFactory()->getAllMetadata();
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document->getName())->drop();
        }
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
        $this->client->request('GET', $url, $parameters, [], $headers);
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
        array $files = [],
    ): ControllerResponse
    {
        $this->client->request('POST', $url, $parameters, $files, $headers, $content);
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
        array $files = [],
    ): ControllerResponse
    {
        $this->client->request('PUT', $url, $parameters, $files, $headers);
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
        $this->client->request('DELETE', $url, [], [], $headers);
        $response = $this->client->getResponse();

        return $this->processResponse($response);
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
        } catch (Throwable $t) {
            $t;

            return new ControllerResponse($response->getStatusCode(), [$response->getContent()]);
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getJwsToken(): string
    {
        $manager = self::getContainer()->get(AuthorizationManager::class);

        return $manager->jwsFromPayload(
            [
                'sub'      => 'user/app/id',
                'eu_sub'   => 'endUser',
                'eu_alias' => 'end_user_human_readable_alias_name',
                'iat'      => time(),
                'exp'      => time() + 3_600,
            ],
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getJweToken(): string
    {
        $payload = [
            'sub'      => 'user/app/id',
            'eu_sub'   => 'endUser',
            'eu_alias' => 'end_user_human_readable_alias_name',
            'iat'      => time(),
            'exp'      => time() + 3_600,
        ];

        $jweSerializer = self::getContainer()->get('jose.jwe_serializer.serializer');
        $jweBuilder    = self::getContainer()->get('jose.jwe_builder.builder');

        $publicKey = self::getContainer()->getParameter('jwe_public_key');
        $jwkPublic = JWKFactory::createFromKey($publicKey);

        $jwe = $jweBuilder
            ->withPayload(Json::encode($payload))
            ->withSharedProtectedHeader(
                [
                    'alg' => (new RSAOAEP256())->name(),// Key Encryption Algorithm
                    'enc' => (new A128GCM())->name(), // Content Encryption Algorithm
                ],
            )
            ->addRecipient($jwkPublic)
            ->build();

        return $jweSerializer->serialize('jwe_compact', $jwe);
    }

    /**
     * @return string
     */
    private function getMongoDatabaseName(): string
    {
        return sprintf('%s%s', $this->dm->getConfiguration()->getDefaultDB(), getenv('TEST_TOKEN'));
    }

}
