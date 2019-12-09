<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Airtable;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
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
     *
     */
    public function testGetApplicationType(): void
    {
        $airtable = self::$container->get('hbpf.application.airtable');
        self::assertEquals(
            ApplicationTypeEnum::CRON,
            $airtable->getApplicationType()
        );
    }

    /**
     *
     */
    public function testGetKey(): void
    {
        $airtable = self::$container->get('hbpf.application.airtable');
        self::assertEquals(
            'airtable',
            $airtable->getKey()
        );
    }

    /**
     *
     */
    public function testName(): void
    {
        $airtable = self::$container->get('hbpf.application.airtable');
        self::assertEquals(
            'Airtable',
            $airtable->getName()
        );
    }

    /**
     *
     */
    public function testGetDescription(): void
    {
        $airtable = self::$container->get('hbpf.application.airtable');
        self::assertEquals(
            'Airtable v1',
            $airtable->getDescription()
        );
    }

    /**
     *
     */
    public function testGetSettingsForm(): void
    {
        $airtable = self::$container->get('hbpf.application.airtable');

        $fields = $airtable->getSettingsForm()->getFields();
        foreach ($fields as $field) {
            self::assertInstanceOf(Field::class, $field);
            self::assertContains($field->getKey(), ['token', 'BASE_ID', 'TABLE_NAME']);
        }

    }

    /**
     *
     */
    public function testGetAccessToken(): void
    {
        $airtable           = self::$container->get('hbpf.application.airtable');
        $applicationInstall = new ApplicationInstall();
        $this->expectException(AuthorizationException::class);
        $airtable->getAccessToken($applicationInstall);

    }

}

