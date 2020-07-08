<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class AirtableApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable
 */
final class AirtableApplicationTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = 'keyfb******LvKNJI';
    public const BASE_ID    = 'appX**********XpN';
    public const TABLE_NAME = 'V******.com';

    /**
     * @var AirtableApplication
     */
    private $app;

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
            self::assertContains($field->getKey(), ['token', 'base_id', 'table_name']);
        }
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->app->getKey()
        );
        $applicationInstall->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        BasicApplicationAbstract::TOKEN => self::API_KEY,
                    ],
                ApplicationAbstract::FORM                         => [
                    AirtableApplication::BASE_ID    => self::BASE_ID,
                    AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                ],
            ]
        );
        $this->pfd($applicationInstall);
        $this->dm->clear();
        self::assertEquals(TRUE, $this->app->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testNoToken(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->app->getKey()
        );
        $applicationInstall->setSettings(
            [
                ApplicationAbstract::FORM => [
                    AirtableApplication::BASE_ID    => self::BASE_ID,
                    AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                ],
            ]
        );
        $this->pfd($applicationInstall);
        $this->dm->clear();
        $this->expectException(AuthorizationException::class);
        $this->app->getRequestDto(
            $applicationInstall,
            'POST'
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = self::$container->get('hbpf.application.airtable');
    }

}
