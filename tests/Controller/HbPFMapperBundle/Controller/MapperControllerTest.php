<?php declare(strict_types=1);

namespace Tests\Controller\HbPFMapperBundle\Controller;

use Hanaboso\PipesFramework\HbPFMapperBundle\Handler\MapperHandler;
use Hanaboso\PipesFramework\HbPFMapperBundle\Loader\MapperLoader;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Token;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Tests\DatabaseWebTestCaseAbstract;

/**
 * Class MapperControllerTest
 *
 * @package Tests\Controller\HbPFMapperBundle\Controller
 */
class MapperControllerTest extends DatabaseWebTestCaseAbstract
{

    /**
     * @var Client
     */
    private $client;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoder;

    /**
     *
     */
    public function testProcess(): void
    {
        $this->client = self::createClient([], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $container    = $this->client->getContainer();

        $this->dm       = $this->container->get('doctrine.odm.mongodb.document_manager');
        $encoderFactory = $container->get('security.encoder_factory');
        $this->encoder  = $encoderFactory->getEncoder(User::class);

        // Login
        $user = $this->loginUser('test@example.com', 'password');

        $mapperHandlerMock = $this->getMockBuilder(MapperHandler::class)
            ->setConstructorArgs([
                new MapperLoader($container),
            ])
            ->setMethods([
                'processTest',
            ])
            ->getMock();

        $mapperHandlerMock->method('processTest')->willReturn('Test');

        $container->set('hbpf.mapper.handler.mapper', $mapperHandlerMock);

        $this->client->request('POST', '/api/mapper/null/process/test', [], [], [], '{"test":1}');

        $code = $this->client->getResponse()->getStatusCode();

        self::assertEquals(200, $code);
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     */
    private function loginUser(string $username, string $password): User
    {
        $user = new User();
        $user
            ->setEmail($username)
            ->setPassword($this->encoder->encodePassword($password, ''));

        $this->persistAndFlush($user);

        $user = $this->getUser($username, $password);

        // $client->getContainer()->get('security.token_storage')->setToken(new Token($user, 'password','secured_area'));

        $session = $this->client->getContainer()->get('hbpf.user.session');
        //$session = new Session(new MockArraySessionStorage());
        //$session = new Session(new MockFileSessionStorage());
        $session->start();

        $firewall = 'secured_area';
        $token    = new UsernamePasswordToken($user->getEmail(), NULL, $firewall, []);
        $session->set('loggedUserId', serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);

        return $user;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return User
     */
    private function getUser(string $username, string $password): User
    {
        $content = [
            'email'    => $username,
            'password' => $password,
        ];

        $this->client->request(
            'POST',
            '/api/user/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json',],
            json_encode($content)
        );

        $abc = $this->client->getResponse();

        $response = json_decode($this->client->getResponse()->getContent(), TRUE);

        $stop = 1;

        return $this->dm->getRepository(User::class)->find($response['id']);
    }

}