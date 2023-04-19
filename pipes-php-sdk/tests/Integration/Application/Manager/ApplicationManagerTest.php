<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Manager;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Crypt\Exceptions\CryptException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\Integration\Application\TestOAuth2NullApplication;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApplicationManagerTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Manager
 */
final class ApplicationManagerTest extends KernelTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @var ApplicationManager
     */
    private ApplicationManager $manager;

    /**
     * @var WebhookManager
     */
    private WebhookManager $webhookManager;

    /**
     * @var ApplicationInstallRepository
     */
    private ApplicationInstallRepository $applicationInstallRepository;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getApplications
     * @throws Exception
     */
    public function testGetApplications(): void
    {
        $this->privateSetUp();
        self::assertEquals(['null', 'null2', 'null3'], $this->manager->getApplications());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getApplication
     *
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $this->privateSetUp();

        self::assertEquals('null-key', $this->manager->getApplication('null')->getName());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::getSynchronousActions
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::isSynchronous
     *
     * @throws Exception
     */
    public function testGetSynchronousActions(): void
    {
        $this->privateSetUp();

        self::assertEquals(['testSynchronous', 'returnBody'], $this->manager->getSynchronousActions('null'));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::runSynchronousAction
     *
     * @throws Exception
     */
    public function testRunSynchronousAction(): void
    {
        $this->privateSetUp();

        $r = new Request([]);
        $r->setMethod(CurlManager::METHOD_GET);

        self::assertEquals(
            'ok',
            $this->manager->runSynchronousAction('null', 'testSynchronous', $r),
        );

        $r = new Request([], ['data']);
        $r->setMethod(CurlManager::METHOD_POST);

        self::assertEquals(
            ['data'],
            $this->manager->runSynchronousAction('null', 'returnBody', $r),
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::runSynchronousAction
     *
     * @throws Exception
     */
    public function testRunSynchronousActionException(): void
    {
        $this->privateSetUp();

        $r = new Request([]);
        $r->setMethod(CurlManager::METHOD_GET);

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::METHOD_NOT_FOUND);
        $this->manager->runSynchronousAction('null', 'notExist', $r);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveApplicationSettings
     *
     * @return void
     * @throws ApplicationInstallException
     * @throws CryptException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testSaveApplicationSettings(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall();
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"user","name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_ObRtYWb+OGRvqTg8PGi26fgX\/bx3nM3Q8AVrpOFM2FU=:r5xJ1EXfKK+DvR98OcqDz6CovUTBr9ii5h4m5rjS4eg=:SDw7MvseSYbStJkhcwUMGfv8wdywHvho:LNPFZ+OQTvLVaUzXTzjSKY1KVtczQcjJlKVocd728JcrE5Ec8RjprP1YpCZIpXHPSbkX+iKF+raxN3i3WKOWh6InyhAGUh5HTfTlHdycaJjLPlkAlZlOxr1F1Jrb","settings":[],"created":"2023-02-07 14:48:15","updated":"2023-02-07 14:48:15","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], Json::encode([])),
                [
                    'created'           => '2023-02-07 14:48:15',
                    'encryptedSettings' => '001_ObRtYWb+OGRvqTg8PGi26fgX/bx3nM3Q8AVrpOFM2FU=:r5xJ1EXfKK+DvR98OcqDz6CovUTBr9ii5h4m5rjS4eg=:SDw7MvseSYbStJkhcwUMGfv8wdywHvho:LNPFZ+OQTvLVaUzXTzjSKY1KVtczQcjJlKVocd728JcrE5Ec8RjprP1YpCZIpXHPSbkX+iKF+raxN3i3WKOWh6InyhAGUh5HTfTlHdycaJjLPlkAlZlOxr1F1Jrb',
                    'updated' => '2023-02-07 14:48:15',
                ],
            ),
        );

        $this->setUpManagers();

        $res = $this->manager->saveApplicationSettings(
            'null',
            'user',
            ['test' => ['b' => 'bValue']],
        );

        self::assertEquals(
            'authorization_form',
            $res[ApplicationManager::APPLICATION_SETTINGS][ApplicationInterface::AUTHORIZATION_FORM]['key'],
        );

        self::assertEquals(
            'testPublicName',
            $res[ApplicationManager::APPLICATION_SETTINGS][ApplicationInterface::AUTHORIZATION_FORM]['publicName'],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveApplicationPassword
     *
     * @throws Exception
     */
    public function testSaveApplicationPassword(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall();
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"user","name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_P7+I5eF8qhpNgD\/hJ6OBbiEZQYO6iypZr3hslnsUDiU=:3i+4cOfb8vltypUNU3zs\/HM9RsiqbMDWQOUUUSyJuJA=:teOlLIozzwd\/wIW8SAZXv4X6GpeSyRIE:BG+whL84D2x7x9taG7zl7yxdWqbdIjAlhtm0kE+4kmEVu\/yDCajXOl3n8r7Znkwz2+49bM0tlDxEZwaOdeq+tRFWOoBCPTFUyWDpAV6Cg2JX9UNvtlkf4Q==","settings":[],"created":"2023-02-07 14:50:24","updated":"2023-02-07 14:50:24","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], Json::encode(
                    [
                        'enabled'              => FALSE,
                        'expires'              => NULL,
                        'id'                   => NULL,
                        'name'                 => 'null',
                        'nonEncryptedSettings' => [],
                        'settings'             => [],
                        'user'                 => 'user',
                    ],
                )),
                [
                    'created'           => '2023-02-07 14:50:24',
                    'encryptedSettings' => '001_P7+I5eF8qhpNgD/hJ6OBbiEZQYO6iypZr3hslnsUDiU=:3i+4cOfb8vltypUNU3zs/HM9RsiqbMDWQOUUUSyJuJA=:teOlLIozzwd/wIW8SAZXv4X6GpeSyRIE:BG+whL84D2x7x9taG7zl7yxdWqbdIjAlhtm0kE+4kmEVu/yDCajXOl3n8r7Znkwz2+49bM0tlDxEZwaOdeq+tRFWOoBCPTFUyWDpAV6Cg2JX9UNvtlkf4Q==',
                    'updated' => '2023-02-07 14:50:24',
                ],
            ),
        );
        $this->setUpManagers();

        $applicationInstall = $this->manager->saveApplicationPassword(
            'null',
            'user',
            ApplicationInterface::AUTHORIZATION_FORM,
            BasicApplicationInterface::PASSWORD,
            'password123',
        );

        self::assertEquals(
            'password123',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::saveAuthorizationToken
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSaveAuthorizationToken(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $applicationInstall = $this->createApplicationInstall(
            'null2',
            'user',
            [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::FRONTEND_REDIRECT_URL => '/test/redirect']],
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"user","name":"null2","nonEncryptedSettings":[],"encryptedSettings":"001_xwZIYhzIkttZeyWj0dDpnRI5WkXbcdq3ObCnMmiOH6w=:xXUVXEpdq+s19FgjJnUCvTN0NMRWvFeLrFNoGryY0SU=:MoQyac6h0L23BO4zUcmGNZ2Q38vKA8kZ:ARQJ7k+8g3fw+CZo+ooLVs07ZyNPUL5sUQRWOyU0qT7EpRhy+7AxJ2ZGBoIYWSkpe0JtgQwHpCls1eovbZU2svMrxyqlznOcwfclQ4TNUjAi7ZzoOZVRryLAjfdSAtDseB2JV91RYcdp","settings":[],"created":"2023-02-07 14:39:49","updated":"2023-02-07 14:39:49","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(
                    200,
                    [],
                    '[{"id":null,"user":"user","name":"null2","nonEncryptedSettings":[],"settings":"1234","created":"2023-02-07 14:00:19","updated":"2023-02-07 14:00:19","expires":null,"enabled":false}]',
                ),
                [
                    'created'           => '2023-02-07 14:39:49',
                    'encryptedSettings' => '001_xwZIYhzIkttZeyWj0dDpnRI5WkXbcdq3ObCnMmiOH6w=:xXUVXEpdq+s19FgjJnUCvTN0NMRWvFeLrFNoGryY0SU=:MoQyac6h0L23BO4zUcmGNZ2Q38vKA8kZ:ARQJ7k+8g3fw+CZo+ooLVs07ZyNPUL5sUQRWOyU0qT7EpRhy+7AxJ2ZGBoIYWSkpe0JtgQwHpCls1eovbZU2svMrxyqlznOcwfclQ4TNUjAi7ZzoOZVRryLAjfdSAtDseB2JV91RYcdp',
                    'updated' => '2023-02-07 14:39:49',
                ],
            ),
        );
        $this->setUpManagers();

        $applicationInstall->setSettings(
            [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::FRONTEND_REDIRECT_URL => '/test/redirect']],
        );

        $app = self::createPartialMock(TestOAuth2NullApplication::class, ['setAuthorizationToken']);
        $app->expects(self::any())->method('setAuthorizationToken')->willReturnSelf();
        $loader = self::createPartialMock(ApplicationLoader::class, ['getApplication']);
        $loader->expects(self::any())->method('getApplication')->willReturn($app);
        $manager = new ApplicationManager($this->applicationInstallRepository, $loader, $this->webhookManager);

        self::assertEquals(
            '/test/redirect',
            $manager->saveAuthorizationToken('null2', 'user', ['code' => ['token']]),
        );
    }

    /**
     * @return void
     * @throws CryptException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetInstalledApplications(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{}]'),
            ),
        );
        $this->setUpManagers();

        $installedApp = $this->manager->getInstalledApplications('user');

        self::assertEquals(1, count($installedApp));
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetInstalledApplicationDetail(): void
    {
        $date             = new DateTime('2022-01-01 00:00:00');
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $originalApplicationInstall = $this->createApplicationInstall('some app', 'example1');
        $this->setUpManagers();
        $originalApplicationInstall->setCreated($date);
        $originalApplicationInstall->setUpdated($date);
        $originalApplicationInstall->setEncryptedSettings('');

        $applicationInstall = $this->manager->getInstalledApplicationDetail('some app', 'example1');
        $applicationInstall->setCreated($date);
        $applicationInstall->setUpdated($date);
        $applicationInstall->setEncryptedSettings('');
        $applicationInstall->setSettings([]);
        self::assertEquals($originalApplicationInstall, $applicationInstall);
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetInstalledApplicationDetailNotFound(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["some app"],"users":["example5"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '{}'),
            ),
        );
        $this->setUpManagers();

        self::expectException(ApplicationInstallException::class);
        self::expectExceptionCode(ApplicationInstallException::APP_WAS_NOT_FOUND);
        $this->manager->getInstalledApplicationDetail('some app', 'example5');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::installApplication
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testInstallApplication(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["something"],"users":["example3"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '{}'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"example3","name":"something","nonEncryptedSettings":[],"encryptedSettings":"","settings":[],"created":"2023-02-08 06:57:22","updated":"2023-02-08 06:57:22","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '{}'),
                ['created' => '2023-02-08 06:57:22', 'updated' => '2023-02-08 06:57:22'],
            ),
        );
        $this->createApplicationInstall('something', 'example3');
        $this->setUpManagers();
        $this->manager->installApplication('something', 'example3');

        $failed = FALSE;
        try {
            $this->applicationInstallRepository->findUserApp('something', 'example3');
        } catch (Exception) {
            $failed = TRUE;
        }
        self::assertEquals(FALSE, $failed);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::installApplication
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testInstallApplicationTest(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall('key');
        $this->setUpManagers();

        self::expectException(ApplicationInstallException::class);
        $this->manager->installApplication('key', 'user');
    }

    /**
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall('null', 'example1');
        $this->mockServer->addMock(
            new Mock('/document/ApplicationInstall', NULL, CurlManager::METHOD_GET, new Response(200, [], '[]')),
        );
        $this->setUpManagers();

        $this->manager->uninstallApplication('null', 'example1');

        $app = $this->applicationInstallRepository->findMany();

        self::assertEquals([], $app);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository::findOneByName
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testApplicationPassword(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall('null', 'example1');
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"example1","name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_BvuBCBrJ7e8vd9UsA821Zxk3U4xbTZmSxA7SfbrWYok=:T4YvPOSj+uZ+EUN8vBnh1QSWHhYswp1di11l6xpxjwI=:V0fpKivn8HW1CLH0ek7kPXl4MKDf9yiL:tSYCWGehNagn2hyVJi+502imCWSzDoF4LsYl0OQu+lH\/jAL1WYScv1v4YNwOC64b1OrZVg6GLq+RyTEcitz5o0tPXpvKxXa2moKsMxO8KS83VsOMRGhr1w==","settings":[],"created":"2023-02-08 07:06:08","updated":"2023-02-08 07:06:08","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '{}'),
                [
                    'created'           => '2023-02-08 07:06:08',
                    'encryptedSettings' => '001_BvuBCBrJ7e8vd9UsA821Zxk3U4xbTZmSxA7SfbrWYok=:T4YvPOSj+uZ+EUN8vBnh1QSWHhYswp1di11l6xpxjwI=:V0fpKivn8HW1CLH0ek7kPXl4MKDf9yiL:tSYCWGehNagn2hyVJi+502imCWSzDoF4LsYl0OQu+lH/jAL1WYScv1v4YNwOC64b1OrZVg6GLq+RyTEcitz5o0tPXpvKxXa2moKsMxO8KS83VsOMRGhr1w==',
                    'updated' => '2023-02-08 07:06:08',
                ],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    '[{"name":"null","encryptedSettings":"001_BvuBCBrJ7e8vd9UsA821Zxk3U4xbTZmSxA7SfbrWYok=:T4YvPOSj+uZ+EUN8vBnh1QSWHhYswp1di11l6xpxjwI=:V0fpKivn8HW1CLH0ek7kPXl4MKDf9yiL:tSYCWGehNagn2hyVJi+502imCWSzDoF4LsYl0OQu+lH\/jAL1WYScv1v4YNwOC64b1OrZVg6GLq+RyTEcitz5o0tPXpvKxXa2moKsMxO8KS83VsOMRGhr1w=="}]',
                ),
            ),
        );
        $this->setUpManagers();

        $this->manager->saveApplicationPassword(
            'null',
            'example1',
            ApplicationInterface::AUTHORIZATION_FORM,
            BasicApplicationInterface::PASSWORD,
            'password123',
        );
        /** @var ApplicationInstall $app */
        $app = $this->applicationInstallRepository->findOneByName('null');

        self::assertEquals(
            'password123',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testApplicationSettings(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall('null', 'example1');
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"example1","name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_HEMX\/bKpm94Myv\/t7wmqxcrrXfprDJWdlKejp9pjcUc=:G3E6MAlfYscXxkVWeIoeqroNIIVagWiUJqQ6rPuv84s=:Hx4xv4if6bHCdDrIe5RVMlFbcIZcs\/vh:sswo7RtnCHP\/GxEkVYnI+KibVeluEhdl7JwVy60rc9lrnUMXpIvq23GmLLLsDQQSDcJQzLodiT5RReo9KsV1w2E8XoS+NqkgIQejzTl6R0wjW5q2z4CwqhVWNvktfQI2cwt2iMRQw7H\/AOHfyBAm+RQFQTOYR9zcvKjs4PLeIb0oaTjed61WdTxOsiR4zsZcIQYu\/kOz3Nt0niAzxKG6+TFaaDkqXAYXVqFk4Ox0j8wrR1B\/dg==","settings":[],"created":"2023-02-08 07:16:53","updated":"2023-02-08 07:16:53","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[]'),
                [
                    'created'           => '2023-02-08 07:16:53',
                    'encryptedSettings' => '001_HEMX/bKpm94Myv/t7wmqxcrrXfprDJWdlKejp9pjcUc=:G3E6MAlfYscXxkVWeIoeqroNIIVagWiUJqQ6rPuv84s=:Hx4xv4if6bHCdDrIe5RVMlFbcIZcs/vh:sswo7RtnCHP/GxEkVYnI+KibVeluEhdl7JwVy60rc9lrnUMXpIvq23GmLLLsDQQSDcJQzLodiT5RReo9KsV1w2E8XoS+NqkgIQejzTl6R0wjW5q2z4CwqhVWNvktfQI2cwt2iMRQw7H/AOHfyBAm+RQFQTOYR9zcvKjs4PLeIb0oaTjed61WdTxOsiR4zsZcIQYu/kOz3Nt0niAzxKG6+TFaaDkqXAYXVqFk4Ox0j8wrR1B/dg==',
                    'updated' => '2023-02-08 07:16:53',
                ],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    '[{"name":"null","user":"testUser","encryptedSettings":"001_HEMX\/bKpm94Myv\/t7wmqxcrrXfprDJWdlKejp9pjcUc=:G3E6MAlfYscXxkVWeIoeqroNIIVagWiUJqQ6rPuv84s=:Hx4xv4if6bHCdDrIe5RVMlFbcIZcs\/vh:sswo7RtnCHP\/GxEkVYnI+KibVeluEhdl7JwVy60rc9lrnUMXpIvq23GmLLLsDQQSDcJQzLodiT5RReo9KsV1w2E8XoS+NqkgIQejzTl6R0wjW5q2z4CwqhVWNvktfQI2cwt2iMRQw7H\/AOHfyBAm+RQFQTOYR9zcvKjs4PLeIb0oaTjed61WdTxOsiR4zsZcIQYu\/kOz3Nt0niAzxKG6+TFaaDkqXAYXVqFk4Ox0j8wrR1B\/dg==","settings":[],"created":"2023-02-08 07:16:53","updated":"2023-02-08 07:16:53"}]',
                ),
            ),
        );
        $this->setUpManagers();

        $this->manager->saveApplicationSettings(
            'null',
            'example1',
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::PASSWORD => 'testPass',
                    BasicApplicationInterface::USER     => 'testUser',
                ],
            ],
        );
        /** @var ApplicationInstall $app */
        $app = $this->applicationInstallRepository->findOneByName('null');

        self::assertEquals(
            'testUser',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
        );

        self::assertEquals(
            'testPass',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testGetSettingsFormValues(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall('null', 'example1');
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"example1","name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_o71NlhKnJYQ6p11UNmc0d1Pj6xUbnG7H6RidyAShaGs=:SpXgmXNqYWTLYXsF1ipgHE6cSTiTp\/f+QxsK0c+eocY=:93VXLj3j+sIU3TIIpONA0yAVIJVk9s61:wu+QHyhuJanT4OuFZ8UM4vl15nmMf6QV3cc43QTZ9kftHxNGmzS0Kb4MuQ2Lvtq+8qrdTav7La2LMd7Hjfdv4ghd7ckUq4bPUV6SVwzkN4y5HIqe3FeZLchdjSgwOwPZ20+epwuWZX3iSSoAWLLopKbB6mhySaXEZoDk+4YNAeLelFP7EAlfUEdprPvjziXBjSAQQGGERO6zyoSpe3BMhMdvaQs1NQ4Zw3ndxk2PeQ==","settings":[],"created":"2023-02-08 07:21:20","updated":"2023-02-08 07:21:20","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[{}]'),
                [
                    'created'           => '2023-02-08 07:21:20',
                    'encryptedSettings' => '001_o71NlhKnJYQ6p11UNmc0d1Pj6xUbnG7H6RidyAShaGs=:SpXgmXNqYWTLYXsF1ipgHE6cSTiTp/f+QxsK0c+eocY=:93VXLj3j+sIU3TIIpONA0yAVIJVk9s61:wu+QHyhuJanT4OuFZ8UM4vl15nmMf6QV3cc43QTZ9kftHxNGmzS0Kb4MuQ2Lvtq+8qrdTav7La2LMd7Hjfdv4ghd7ckUq4bPUV6SVwzkN4y5HIqe3FeZLchdjSgwOwPZ20+epwuWZX3iSSoAWLLopKbB6mhySaXEZoDk+4YNAeLelFP7EAlfUEdprPvjziXBjSAQQGGERO6zyoSpe3BMhMdvaQs1NQ4Zw3ndxk2PeQ==',
                    'updated' => '2023-02-08 07:21:20',
                ],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"],"users":["example1"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    '[{"id":null,"user":"example1","name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_o71NlhKnJYQ6p11UNmc0d1Pj6xUbnG7H6RidyAShaGs=:SpXgmXNqYWTLYXsF1ipgHE6cSTiTp\/f+QxsK0c+eocY=:93VXLj3j+sIU3TIIpONA0yAVIJVk9s61:wu+QHyhuJanT4OuFZ8UM4vl15nmMf6QV3cc43QTZ9kftHxNGmzS0Kb4MuQ2Lvtq+8qrdTav7La2LMd7Hjfdv4ghd7ckUq4bPUV6SVwzkN4y5HIqe3FeZLchdjSgwOwPZ20+epwuWZX3iSSoAWLLopKbB6mhySaXEZoDk+4YNAeLelFP7EAlfUEdprPvjziXBjSAQQGGERO6zyoSpe3BMhMdvaQs1NQ4Zw3ndxk2PeQ==","settings":[],"created":"2023-02-08 07:21:20","updated":"2023-02-08 07:21:20","expires":null,"enabled":false}]',
                ),
            ),
        );
        $this->setUpManagers();

        $this->manager->saveApplicationSettings(
            'null',
            'example1',
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    'settings3'                         => 'secret',
                    BasicApplicationInterface::PASSWORD => 'data2',
                    BasicApplicationInterface::USER     => 'data1',
                ],
            ],
        );
        $values = $this->manager->getApplicationSettings('null', 'example1');

        self::assertEquals(
            BasicApplicationInterface::USER,
            $values[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][0]['key'],
        );
        self::assertEquals(
            'data1',
            $values[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS][0]['value'],
        );
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSetApplicationSettingForm(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->createApplicationInstall('null', 'example1');
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"created":"2023-02-08 07:21:20","enabled":false,"encryptedSettings":"001_OwOydBUnllknSduqitxQXqLDPdCmAazvxNtOVt2DRy8=:g5SthlOq6CrY1klYlcjZP6cHU+QFdv\/6dpLD49H6ReM=:+u1sXjDqWvCvYpbnQSmObUNbKHw1zMwJ:JnVqv\/nBuzw2azcT+JVxCni\/LbCIxNt17z8fXk\/aRlQ5BXQKxXp0FibA5s6hN3Z9Hqkgc2h3OhRxrMQUIqd3mH4ERBwlHBoY7W3n2YN5kDHIbw0jCGsERoqXzYkQ4q53WJJYBSArJQrV1AzPk1dff8NhkK3JKOoowPs4720bw538fVTzI9oVofiAMu2Z8+lvF4eQPTPvLYQC6wHY8ClRi0y2DG2IWp3mgfknP3rBrA==","expires":null,"id":null,"nonEncryptedSettings":[],"settings":[],"updated":"2023-02-08 07:21:20","name":"null","user":"example1"}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[{}]'),
                [
                    'created'           => '2023-02-08 07:21:20',
                    'encryptedSettings' => '001_o71NlhKnJYQ6p11UNmc0d1Pj6xUbnG7H6RidyAShaGs=:SpXgmXNqYWTLYXsF1ipgHE6cSTiTp/f+QxsK0c+eocY=:93VXLj3j+sIU3TIIpONA0yAVIJVk9s61:wu+QHyhuJanT4OuFZ8UM4vl15nmMf6QV3cc43QTZ9kftHxNGmzS0Kb4MuQ2Lvtq+8qrdTav7La2LMd7Hjfdv4ghd7ckUq4bPUV6SVwzkN4y5HIqe3FeZLchdjSgwOwPZ20+epwuWZX3iSSoAWLLopKbB6mhySaXEZoDk+4YNAeLelFP7EAlfUEdprPvjziXBjSAQQGGERO6zyoSpe3BMhMdvaQs1NQ4Zw3ndxk2PeQ==',
                    'updated' => '2023-02-08 07:21:20',
                ],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    '[{"created":"2023-02-08 07:21:20","enabled":false,"encryptedSettings":"001_emfnbPpKcBxmznhBWnWSAv5o8UhayuAjtqOpWplnEN0=:uU\/+t7tTMon4+9SX69jgDICNI2CC22BX86l4rixpRSs=:MSrf+ajtaGhNHTic\/uclacy\/S8fztnu7:nIPHc46JEy7Bazb9SIKuqULR7BiwpFzN0RD20QkdEU8BhBP9HavF3whOgGsjVLNIhQQEuMFnXt3ReXcSI3ihTa6q8O0mrpkflffmMYqMivsNoJmi+oJTUr3btWx1xS1LFztkqjrdbolSVP4aaI9bL3x2dhkpypyXFXaduKCEl6JvgUb\/EdEN9Z0nyvZcj3NxMm9zBE2GH5flumPoZzrsPtnySKhhK9k8TAiqmga\/vw==","expires":null,"id":null,"nonEncryptedSettings":[],"settings":[],"updated":"2023-02-08 07:21:20","name":"null","user":"example1"}]',
                ),
            ),
        );
        $this->setUpManagers();

        $this->manager->saveApplicationSettings(
            'null',
            'example1',
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    BasicApplicationInterface::PASSWORD => 'data2',
                    BasicApplicationInterface::USER     => 'data1',
                ],
            ],
        );

        /** @var ApplicationInstall $app */
        $app = $this->applicationInstallRepository->findOneByName('null');

        self::assertEquals(
            'data1',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
        );
        self::assertEquals(
            'data2',
            $app->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::PASSWORD],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::subscribeWebhooks
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSubscribeWebhooks(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $applicationInstall = $this->createApplicationInstall();
        $this->setUpManagers();

        $this->manager->subscribeWebhooks($applicationInstall);

        self::assertFake();
    }

    /**
     * @throws Exception
     */
    private function privateSetUp(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->setUpManagers();
    }

    /**
     * @throws Exception
     */
    private function setUpManagers(): void
    {
        $this->manager                      = self::getContainer()->get('hbpf.application.manager');
        $this->webhookManager               = self::getContainer()->get('hbpf.application.manager.webhook');
        $this->applicationInstallRepository = self::getContainer()->get('hbpf.application_install.repository');
    }

    /**
     * @param string  $key
     * @param string  $user
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     * @throws CryptException
     */
    private function createApplicationInstall(
        string $key = 'null',
        string $user = 'user',
        array $settings = ['applicationSettings' => ['test' => ['a' => 'aValue']]],
    ): ApplicationInstall
    {
        /** @var CryptManager $cryptManager */
        $cryptManager       = self::getContainer()->get('hbpf.commons.crypt.crypt_manager');
        $applicationInstall = (new ApplicationInstall())
            ->setKey($key)
            ->setUser($user)
            ->setEncryptedSettings($cryptManager->encrypt($settings));

        $this->mockServer->addMock(
            new Mock(
                sprintf('/document/ApplicationInstall?filter={"names":["%s"],"users":["%s"]}', $key, $user),
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        return $applicationInstall;
    }

}
