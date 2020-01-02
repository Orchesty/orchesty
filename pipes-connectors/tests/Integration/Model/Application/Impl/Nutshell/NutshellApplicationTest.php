<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Nutshell;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Nutshell\NutshellApplication;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class NutshellApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Nutshell
 */
final class NutshellApplicationTest extends DatabaseTestCaseAbstract
{

    public const USER = 'user@user.com';
    public const API_KEY = '271cca5c67c**********427b659988cc38e2f78';

    /**
     * @var NutshellApplication
     */
    private $application;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->application = self::$container->get('hbpf.application.nutshell');
    }

    /**
     * @throws Exception
     */
    public function testAuthorization(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->application->getKey(),
            self::USER,
            self::API_KEY
        );

        $this->pf($applicationInstall);

        $dto = $this->application->getRequestDto(
            $applicationInstall,
            'POST',
            'http://api.nutshell.com/v1/json',
            '{"id": "apeye", "method": "getLead", "params": { "leadId": 1000 }, "data":{"username": "user@user.com"} }'
        );

        $this->assertEquals('POST', $dto->getMethod());
        $this->assertEquals('http://api.nutshell.com/v1/json', $dto->getUriString());
        $this->assertEquals(
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
            self::assertInstanceOf(Field::class, $field);
            self::assertContains($field->getKey(), ['user', 'password']);
        }

    }

}
