<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\String\Json;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use UserBundleTests\JwtUserTrait;

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
    use JwtUserTrait;
    use LoginJwtTestTrait;

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
        $this->startClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();

        // Login
        [$this->user, $this->jwt] = $this->loginUser('test@example.com', 'password');
    }

    /**
     * @param string $url
     * @param bool   $withLogin
     *
     * @return object
     * @throws Exception
     */
    protected function sendGet(string $url, bool $withLogin = FALSE): object
    {
        if ($withLogin) {
            $this->client->request('GET', $url, server: [self::$AUTHORIZATION => $this->jwt]);
        } else {
            $this->client->request('GET', $url);
        }
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
            [self::$AUTHORIZATION => $this->jwt],
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
            [self::$AUTHORIZATION => $this->jwt],
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
        $this->client->request('DELETE', $url, server: [self::$AUTHORIZATION => $this->jwt]);
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
            'status'  => $response->getStatusCode(),
            'content' => (object) $content,
        ];
    }

}
