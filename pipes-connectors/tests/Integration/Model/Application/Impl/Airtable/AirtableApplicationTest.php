<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Airtable;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AirtableApplicationTest
 *
 * @package Tests\Integration\Model\Application\Impl\Airtable
 */
final class AirtableApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @var AirtableApplication
     */
    private $app;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app = self::$container->get('hbpf.application.airtable');
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON, $this->app->getApplicationType());
    }

    /**
     *
     */
    public function testGetKey(): void
    {
        self::assertEquals('airtable', $this->app->getKey());
    }

    /**
     * @throws Exception
     */
    public function testName(): void
    {
        self::assertEquals('Airtable', $this->app->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertEquals('Airtable v1', $this->app->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $fields = $this->app->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains($field->getKey(), ['token', 'BASE_ID', 'TABLE_NAME']);
        }
    }

    /**
     * @throws Exception
     */
    public function testGetAccessToken(): void
    {
        $applicationInstall = new ApplicationInstall();
        $this->expectException(AuthorizationException::class);
        $this->app->getAccessToken($applicationInstall);
    }

}

