<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use LogicException;

/**
 * Class S3ApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3
 */
final class S3ApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var S3Application
     */
    private $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::getKey
     */
    public function testGetKey(): void
    {
        self::assertEquals('s3', $this->application->getKey());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::getName
     */
    public function testGetName(): void
    {
        self::assertEquals('Amazon Simple Storage Service', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::getDescription
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Amazon Simple Storage Service (Amazon S3) is an object storage service that offers industry-leading scalability, data availability, security, and performance.',
            $this->application->getDescription()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::getRequestDto
     */
    public function testGetRequestDto(): void
    {
        self::assertException(
            LogicException::class,
            0,
            sprintf(
                "Method '%s::getRequestDto' is not supported! Use '%s::getConnection' instead!",
                S3Application::class,
                S3Application::class
            )
        );

        $this->application->getRequestDto(new ApplicationInstall(), '');
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        foreach ($this->application->getSettingsForm()->getFields() as $field) {
            self::assertContains(
                $field->getKey(),
                [
                    S3Application::KEY,
                    S3Application::SECRET,
                    S3Application::BUCKET,
                    S3Application::REGION,
                    S3Application::ENDPOINT,
                ]
            );
        }
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $application = (new ApplicationInstall())->setSettings(
            [
                BasicApplicationAbstract::FORM => [
                    S3Application::KEY      => 'Key',
                    S3Application::SECRET   => 'Secret',
                    S3Application::REGION   => 'eu-central-1',
                    S3Application::BUCKET   => 'Bucket',
                    S3Application::ENDPOINT => 'http://fakes3:4567',
                ],
            ]
        );

        $this->dm->persist($application);
        $this->dm->flush();
        $this->dm->refresh($application);

        self::assertTrue($this->application->isAuthorized($application));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application::isAuthorized
     *
     * @throws Exception
     */
    public function testIsNotAuthorized(): void
    {
        $application = new ApplicationInstall();

        $this->dm->persist($application);
        $this->dm->flush();
        $this->dm->refresh($application);

        self::assertFalse($this->application->isAuthorized($application));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.s3');
    }

}
