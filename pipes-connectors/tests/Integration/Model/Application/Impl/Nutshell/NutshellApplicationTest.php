<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Nutshell;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
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
     * @throws Exception
     */
    public function testAuthorization(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall($this->application->getKey(), self::USER, self::API_KEY);
        $this->pf($applicationInstall);

        $dto = $this->application->getRequestDto(
            $applicationInstall,
            'POST',
            'http://api.nutshell.com/v1/json',
            '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }'
        );

        self::assertEquals('POST', $dto->getMethod());
        self::assertEquals('http://api.nutshell.com/v1/json', $dto->getUriString());
        self::assertEquals(
            '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }',
            $dto->getBody()
        );
    }

    /**
     *
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(
            ApplicationTypeEnum::CRON,
            $this->application->getApplicationType()
        );
    }

    /**
     *
     */
    public function testName(): void
    {
        self::assertEquals(
            'Nutshell',
            $this->application->getName()
        );
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        self::assertEquals(
            'Nutshell v1',
            $this->application->getDescription()
        );
    }

    /**
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
