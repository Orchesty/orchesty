<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\AmazonApps\Redshift;

use Aws\Redshift\Exception\RedshiftException;
use Aws\Redshift\RedshiftClient;
use Aws\Result;
use Closure;
use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use LogicException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class RedshiftApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\AmazonApps\Redshift
 */
final class RedshiftApplicationTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;
    use PHPMock;

    /**
     * @var RedshiftApplication
     */
    private $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getKey
     */
    public function testGetKey(): void
    {
        self::assertEquals('redshift', $this->application->getKey());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('Amazon Redshift', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getDescription
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Amazon Redshift is a fast, simple, cost-effective data warehousing service.',
            $this->application->getDescription()
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
                RedshiftApplication::class
            )
        );

        $this->application->getRequestDto(new ApplicationInstall(), '');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        foreach ($this->application->getSettingsForm()->getFields() as $field) {
            self::assertContains(
                $field->getKey(),
                [
                    RedshiftApplication::KEY,
                    RedshiftApplication::SECRET,
                    RedshiftApplication::DB_PASSWORD,
                    RedshiftApplication::REGION,
                ]
            );
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
                BasicApplicationAbstract::FORM => [
                    RedshiftApplication::KEY         => 'Key',
                    RedshiftApplication::SECRET      => 'Secret',
                    RedshiftApplication::REGION      => 'eu-central-1',
                    RedshiftApplication::DB_PASSWORD => 'dbPasswd',
                ],
            ]
        );

        $this->dm->persist($application);
        $this->dm->flush();

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

        $this->dm->persist($application);
        $this->dm->flush();

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
            RedshiftApplication::KEY         => 'Key',
            RedshiftApplication::SECRET      => 'Secret',
            RedshiftApplication::REGION      => 'eu-central-1',
            RedshiftApplication::DB_PASSWORD => 'dbPasswd',
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
        /** @var RedshiftClient|MockObject $client */
        $client = self::createPartialMock(RedshiftClient::class, ['__call']);
        $client->method('__call')->willReturnCallback(
            static fn(): Result => new Result(
                [
                    'Clusters' => [
                        [
                            'ClusterIdentifier' => '',
                            'MasterUsername'    => '',
                            'DBName'            => '',
                            'Endpoint'          => [
                                'Address' => '',
                                'Port'    => '',
                            ],
                        ],
                    ],
                ]
            )
        );

        /** @var RedshiftApplication|MockObject $innerApplication */
        $innerApplication = self::createPartialMock(RedshiftApplication::class, ['getRedshiftClient']);
        $innerApplication->method('getRedshiftClient')->willReturn($client);

        $settings = [
            RedshiftApplication::KEY         => 'Key',
            RedshiftApplication::SECRET      => 'Secret',
            RedshiftApplication::REGION      => 'eu-central-1',
            RedshiftApplication::DB_PASSWORD => 'dbPasswd',
        ];

        $application = (new ApplicationInstall())->setSettings($settings);
        $application = $innerApplication->setApplicationSettings($application, $settings);

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
                ]
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
        self::assertException(
            ApplicationInstallException::class,
            0,
            'Login into application was unsuccessful.'
        );

        /** @var RedshiftClient|MockObject $client */
        $client = self::createPartialMock(RedshiftClient::class, ['__call']);
        $client->method('__call')->willReturnCallback(static fn(): Result => new Result(['Clusters' => [FALSE]]));

        /** @var RedshiftApplication|MockObject $innerApplication */
        $innerApplication = self::createPartialMock(RedshiftApplication::class, ['getRedshiftClient']);
        $innerApplication->method('getRedshiftClient')->willReturn($client);

        $settings = [
            RedshiftApplication::KEY         => 'Key',
            RedshiftApplication::SECRET      => 'Secret',
            RedshiftApplication::REGION      => 'eu-central-1',
            RedshiftApplication::DB_PASSWORD => 'dbPasswd',
        ];

        $innerApplication->setApplicationSettings((new ApplicationInstall())->setSettings($settings), $settings);
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift\RedshiftApplication::getConnection
     *
     * @throws Exception
     */
    public function testGetConnection(): void
    {
        $this->prepareConnection(static fn() => TRUE);

        $settings = [
            'host'           => '',
            'Port'           => '',
            'DBName'         => '',
            'MasterUsername' => '',
            'Address'        => '',
            'DbPassword'     => '',
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
        self::assertException(
            ApplicationInstallException::class,
            0,
            'Connection to Redshift db was unsuccessful.'
        );

        $this->prepareConnection(static fn() => FALSE);

        $this->application->getConnection(
            (new ApplicationInstall())->setSettings(
                [
                    'host'           => '',
                    'Port'           => '',
                    'DBName'         => '',
                    'MasterUsername' => '',
                    'Address'        => '',
                    'DbPassword'     => '',
                ]
            )
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.redshift');
    }

    /**
     * @param Closure $closure
     */
    private function prepareConnection(Closure $closure): void
    {
        /** @var MockObject $connection */
        $connection = $this->getFunctionMock(
            'Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift',
            'pg_connect'
        );

        $connection->expects(self::any())->willReturnCallback($closure);
    }

}
