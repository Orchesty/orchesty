<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class NutshellApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell
 */
final class NutshellApplicationTest extends DatabaseTestCaseAbstract
{

    public const USER    = 'user@user.com';
    public const API_KEY = '271cca5c67c**********427b659988cc38e2f78';

    /**
     * @var NutshellApplication
     */
    private $application;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication::getRequestDto
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication::getToken
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication::getKey
     *
     * @throws Exception
     */
    public function testAuthorization(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall($this->application->getKey(), self::USER, self::API_KEY);
        $this->pf($applicationInstall);

        $dto = $this->application->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_POST,
            'http://app.nutshell.com/api/v1/json',
            '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }'
        );

        self::assertEquals('POST', $dto->getMethod());
        self::assertEquals('http://app.nutshell.com/api/v1/json', $dto->getUriString());
        self::assertEquals(
            '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }',
            $dto->getBody()
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication::getApplicationType
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON, $this->application->getApplicationType());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication::getName
     */
    public function testName(): void
    {
        self::assertEquals('Nutshell', $this->application->getName());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication::getDescription
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Nutshell v1', $this->application->getDescription());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $fields = $this->application->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertContains($field->getKey(), ['user', 'password']);
        }
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = self::$container->get('hbpf.application.nutshell');
    }

}
