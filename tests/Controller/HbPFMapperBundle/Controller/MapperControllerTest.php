<?php declare(strict_types=1);

namespace Tests\Controller\HbPFMapperBundle\Controller;

use Hanaboso\PipesFramework\HbPFMapperBundle\Handler\MapperHandler;
use Hanaboso\PipesFramework\HbPFMapperBundle\Loader\MapperLoader;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Token;
use Symfony\Component\BrowserKit\Cookie;
use Tests\DatabaseWebTestCaseAbstract;

/**
 * Class MapperControllerTest
 *
 * @package Tests\Controller\HbPFMapperBundle\Controller
 */
class MapperControllerTest extends DatabaseWebTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $client    = self::createClient([], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $container = $client->getContainer();

        $user = (new User())
            ->setEmail('user@example.com')
            ->setPassword('passw0rd');

        $this->persistAndFlush($user);

       // $client->getContainer()->get('security.token_storage')->setToken(new Token($user, 'password','secured_area'));

        $session = $client->getContainer()->get('hbpf.user.session');

        $session->set('loggedUserId', $user->getId());

        $a = $session->getId();
        $b = $session->getName();

        $session->save();

      //  die(var_dump($b));

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);


        $mapperHandlerMock = $this->getMockBuilder(MapperHandler::class)
            ->setConstructorArgs([
                new MapperLoader($container),
            ])
            ->setMethods([
                'processTest',
            ])
            ->getMock();

        $mapperHandlerMock->method('processTest')->willReturn('Test');

        $container = $client->getContainer();
        $container->set('hbpf.mapper.handler.mapper', $mapperHandlerMock);

        $client->request('POST', '/api/mapper/null/process/test', [], [], [], '{"test":1}');

        $code = $client->getResponse()->getStatusCode();

        self::assertEquals(200, $code);

        $stop = 1;

        //    $client->request();
    }
}