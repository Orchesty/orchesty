<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManager;
use Hanaboso\UserBundle\Model\Token;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package PipesFrameworkTests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use ControllerTestTrait;
    use DatabaseTestTrait;
    use CustomAssertTrait;

    /**
     * @var NativePasswordEncoder
     */
    protected NativePasswordEncoder $encoder;

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

        // Login
        $this->loginUser('test@example.com', 'password');
    }

    /**
     * @param object $document
     *
     * @throws Exception
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     * @throws Exception
     */
    protected function loginUser(string $username, string $password): User
    {
        $this->session      = self::$container->get('session');
        $this->tokenStorage = self::$container->get('security.token_storage');
        $this->session->invalidate();
        $this->session->start();

        $user = new User();
        $user
            ->setEmail($username)
            ->setPassword($this->encoder->encodePassword($password, ''));

        $this->persistAndFlush($user);

        $token = new Token($user, $password, SecurityManager::SECURED_AREA, ['admin']);
        $this->tokenStorage->setToken($token);

        $this->session->set(
            sprintf('%s%s', SecurityManager::SECURITY_KEY, SecurityManager::SECURED_AREA),
            serialize($token)
        );
        $this->session->save();

        $cookie = new Cookie($this->session->getName(), $this->session->getId());
        $this->client->getCookieJar()->set($cookie);

        return $user;
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
