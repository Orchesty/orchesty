<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class NutshellApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell
 */
#[CoversClass(NutshellApplication::class)]
final class NutshellApplicationTest extends KernelTestCaseAbstract
{

    public const USER    = 'user@user.com';
    public const API_KEY = '271cca5c67c**********427b659988cc38e2f78';

    /**
     * @var NutshellApplication
     */
    private NutshellApplication $application;

    /**
     * @throws Exception
     */
    public function testAuthorization(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->application->getName(),
            self::USER,
            self::API_KEY,
        );

        $dto = $this->application->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            'https://app.nutshell.com/api/v1/json',
            '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }',
        );

        self::assertEquals('POST', $dto->getMethod());
        self::assertEquals('https://app.nutshell.com/api/v1/json', $dto->getUriString());
        self::assertEquals(
            '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }',
            $dto->getBody(),
        );
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
    public function testPublicName(): void
    {
        self::assertEquals('Nutshell', $this->application->getPublicName());
    }

    /**
     * @return void
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Nutshell v1', $this->application->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->application->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains($field->getKey(), ['user', 'password']);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::getContainer()->get('hbpf.application.nutshell');
    }

}
