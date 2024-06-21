<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3\S3Application;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class S3ApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\AmazonApps\S3
 */
#[CoversClass(S3Application::class)]
final class S3ApplicationTest extends KernelTestCaseAbstract
{

    /**
     * @var S3Application
     */
    private S3Application $application;

    /**
     * @return void
     */
    public function testGetKey(): void
    {
        self::assertEquals('s3', $this->application->getName());
    }

    /**
     * @return void
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON->value, $this->application->getApplicationType());
    }

    /**
     * @return void
     */
    public function testGetPublicName(): void
    {
        self::assertEquals('Amazon Simple Storage Service', $this->application->getPublicName());
    }

    /**
     * @return void
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Amazon Simple Storage Service (Amazon S3) is an object storage service that offers industry-leading scalability, data availability, security, and performance.',
            $this->application->getDescription(),
        );
    }

    /**
     * @return void
     */
    public function testGetRequestDto(): void
    {
        self::assertException(
            LogicException::class,
            0,
            sprintf(
                "Method '%s::getRequestDto' is not supported! Use '%s::getConnection' instead!",
                S3Application::class,
                S3Application::class,
            ),
        );

        $this->application->getRequestDto(new ProcessDto(), new ApplicationInstall(), '');
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        foreach ($this->application->getFormStack()->getForms() as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains(
                    $field->getKey(),
                    [
                        S3Application::KEY,
                        S3Application::SECRET,
                        S3Application::BUCKET,
                        S3Application::REGION,
                        S3Application::ENDPOINT,
                    ],
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $application = (new ApplicationInstall())->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    S3Application::BUCKET   => 'Bucket',
                    S3Application::ENDPOINT => 'http://fakes3:4567',
                    S3Application::KEY      => 'Key',
                    S3Application::REGION   => 'eu-central-1',
                    S3Application::SECRET   => 'Secret',
                ],
            ],
        );

        self::assertTrue($this->application->isAuthorized($application));
    }

    /**
     * @throws Exception
     */
    public function testIsNotAuthorized(): void
    {
        $application = new ApplicationInstall();
        self::assertFalse($this->application->isAuthorized($application));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.s3');
    }

}
