<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManager;
use Hanaboso\PipesFramework\User\Model\Token;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

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
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var BCryptPasswordEncoder
     */
    protected $encoder;

    /**
     * DatabaseTestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();
        self::bootKernel();
        $this->container    = self::$kernel->getContainer();
        $this->client       = self::createClient([], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $this->dm           = $this->container->get('doctrine_mongodb.odm.default_document_manager');
        $this->session      = $this->container->get('hbpf.user.session');
        $this->tokenStorage = $this->container->get('security.token_storage');
        $encoderFactory     = $this->container->get('security.encoder_factory');
        $this->encoder      = $encoderFactory->getEncoder(User::class);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dm->getConnection()->dropDatabase('pipes');
        $this->session->invalidate();
        $this->session->clear();

        // Login
        $this->loginUser('test@example.com', 'password');
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
     * @param string $username
     * @param string $password
     *
     * @return User
     */
    protected function loginUser(string $username, string $password): User
    {
        $user = new User();
        $user
            ->setEmail($username)
            ->setPassword($this->encoder->encodePassword($password, ''));

        $this->persistAndFlush($user);

        $token = new Token($user, $password, SecurityManager::SECURED_AREA);
        $this->tokenStorage->setToken($token);

        $this->session->set(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA, serialize($token));
        $this->session->save();

        $cookie = new Cookie($this->session->getName(), $this->session->getId());
        $this->client->getCookieJar()->set($cookie);

        return $user;
    }

}