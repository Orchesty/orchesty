<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\Redshift;

use Aws\Redshift\Exception\RedshiftException;
use Aws\Redshift\RedshiftClient;
use Aws\Result;
use Closure;
use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use LogicException;
use PgSql\Connection;
use phpmock\phpunit\PHPMock;

/**
 * Class RedshiftApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\Redshift
 */
final class RedshiftApplicationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;
    use PHPMock;

    /**
     * @var RedshiftApplication
     */
    private RedshiftApplication $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getName
     */
    public function testGetKey(): void
    {
        self::assertEquals('redshift', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON->value, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getPublicName
     */
    public function testGetPublicName(): void
    {
        self::assertEquals('Amazon Redshift', $this->application->getPublicName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getDescription
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Amazon Redshift is a fast, simple, cost-effective data warehousing service.',
            $this->application->getDescription(),
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getRequestDto
     */
    public function testGetRequestDto(): void
    {
        self::assertException(
            LogicException::class,
            0,
            sprintf(
                "Method '%s::getRequestDto' is not supported! Use '%s::getConnection' instead!",
                RedshiftApplication::class,
                RedshiftApplication::class,
            ),
        );

        $this->application->getRequestDto(new ProcessDto(), new ApplicationInstall(), '');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getFormStack
     *
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains(
                    $field->getKey(),
                    [
                        RedshiftApplication::KEY,
                        RedshiftApplication::SECRET,
                        RedshiftApplication::DB_PASSWORD,
                        RedshiftApplication::REGION,
                    ],
                );
            }
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $application = (new ApplicationInstall())->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    RedshiftApplication::DB_PASSWORD => 'dbPasswd',
                    RedshiftApplication::KEY         => 'Key',
                    RedshiftApplication::REGION      => 'eu-central-1',
                    RedshiftApplication::SECRET      => 'Secret',
                ],
            ],
        );

        self::assertTrue($this->application->isAuthorized($application));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testIsNotAuthorized(): void
    {
        $application = new ApplicationInstall();

        self::assertFalse($this->application->isAuthorized($application));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getRedshiftClient
     *
     * @throws Exception
     */
    public function testGetRedshiftClientException(): void
    {
        self::expectException(RedshiftException::class);

        $settings = [
            ApplicationInterface::AUTHORIZATION_FORM => [
                RedshiftApplication::DB_PASSWORD => 'dbPasswd',
                RedshiftApplication::KEY         => 'Key',
                RedshiftApplication::REGION      => 'eu-central-1',
                RedshiftApplication::SECRET      => 'Secret',
            ],
        ];

        $this->application->setApplicationSettings((new ApplicationInstall())->setSettings($settings), $settings);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::setApplicationSettings
     *
     * @throws Exception
     */
    public function testSetApplicationInstall(): void
    {
        $callback = static fn(): Result => new Result(
            [
                'Clusters' => [
                    [
                        'ClusterIdentifier' => '',
                        'DBName'            => '',
                        'Endpoint'          => [
                            'Address' => '',
                            'Port'    => '',
                        ],
                        'MasterUsername'    => '',
                    ],
                ],
            ],
        );

        $client = self::createPartialMock(RedshiftClient::class, ['__call']);
        $client->method('__call')->willReturnCallback($callback);

        $innerApplication = self::createPartialMock(RedshiftApplication::class, ['getRedshiftClient']);
        $innerApplication->method('getRedshiftClient')->willReturn($client);

        $settings = [
            RedshiftApplication::DB_PASSWORD => 'dbPasswd',
            RedshiftApplication::KEY         => 'Key',
            RedshiftApplication::REGION      => 'eu-central-1',
            RedshiftApplication::SECRET      => 'Secret',
        ];

        $application = (new ApplicationInstall())->setSettings([ApplicationInterface::AUTHORIZATION_FORM => $settings]);
        $application = $innerApplication->setApplicationSettings(
            $application,
            [ApplicationInterface::AUTHORIZATION_FORM => $settings],
        );

        foreach (array_keys($application->getSettings()) as $setting) {
            self::assertContains(
                $setting,
                [
                    'key',
                    'secret',
                    'region',
                    'DbPassword',
                    'form',
                    'ClusterIdentifier',
                    'MasterUsername',
                    'DBName',
                    'host',
                    'Port',
                ],
            );
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::setApplicationSettings
     *
     * @throws Exception
     */
    public function testSetApplicationInstallException(): void
    {
        self::assertException(ApplicationInstallException::class, 0, 'Login into application was unsuccessful.');

        $client = self::createPartialMock(RedshiftClient::class, ['__call']);
        $client->method('__call')->willReturnCallback(static fn(): Result => new Result(['Clusters' => [FALSE]]));

        $innerApplication = self::createPartialMock(RedshiftApplication::class, ['getRedshiftClient']);
        $innerApplication->method('getRedshiftClient')->willReturn($client);

        $settings = [
            RedshiftApplication::DB_PASSWORD => 'dbPasswd',
            RedshiftApplication::KEY         => 'Key',
            RedshiftApplication::REGION      => 'eu-central-1',
            RedshiftApplication::SECRET      => 'Secret',
        ];

        $innerApplication->setApplicationSettings(
            (new ApplicationInstall())->setSettings([ApplicationInterface::AUTHORIZATION_FORM => $settings]),
            [ApplicationInterface::AUTHORIZATION_FORM => $settings],
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getConnection
     *
     * @throws Exception
     */
    public function testGetConnection(): void
    {
        self::markTestSkipped('PGMock fails');
        $this->prepareConnection(static fn() => new Connection());

        $settings = [
            'Address'        => '',
            'DBName'         => '',
            'DbPassword'     => '',
            'host'           => '',
            'MasterUsername' => '',
            'Port'           => '',
        ];

        $application = (new ApplicationInstall())->setSettings($settings);
        $this->application->getConnection($application);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getConnection
     *
     * @throws Exception
     */
    public function testGetConnectionException(): void
    {
        self::markTestSkipped('PGMock fails');
        self::assertException(ApplicationInstallException::class, 0, 'Connection to Redshift db was unsuccessful.');

        $this->prepareConnection(static fn() => new Connection());

        $this->application->getConnection(
            (new ApplicationInstall())->setSettings(
                [
                    'Address'        => '',
                    'DBName'         => '',
                    'DbPassword'     => '',
                    'host'           => '',
                    'MasterUsername' => '',
                    'Port'           => '',
                ],
            ),
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.redshift');
    }

    /**
     * @param Closure $closure
     */
    protected function prepareConnection(Closure $closure): void
    {
        $connection = $this->getFunctionMock(
            'Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift',
            'pg_connect',
        );

        $connection->expects(self::any())->willReturnCallback($closure);
    }

}
