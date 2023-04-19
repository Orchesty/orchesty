<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable;

use Exception;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Airtable\AirtableApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;

/**
 * Class AirtableApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Airtable
 */
final class AirtableApplicationTest extends KernelTestCaseAbstract
{

    public const API_KEY    = 'keyfb******LvKNJI';
    public const BASE_ID    = 'appX**********XpN';
    public const TABLE_NAME = 'V******.com';

    /**
     * @var AirtableApplication
     */
    private AirtableApplication $app;

    /**
     * @throws Exception
     */
    public function testGetApplicationType(): void
    {
        self::assertEquals(ApplicationTypeEnum::CRON->value, $this->app->getApplicationType());
    }

    /**
     *
     */
    public function testGetKey(): void
    {
        self::assertEquals('airtable', $this->app->getName());
    }

    /**
     * @throws Exception
     */
    public function testPublicName(): void
    {
        self::assertEquals('Airtable', $this->app->getPublicName());
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
    public function testGetFormStack(): void
    {
        $forms = $this->app->getFormStack()->getForms();
        foreach ($forms as $form) {
            foreach ($form->getFields() as $field) {
                self::assertContains($field->getKey(), ['token', 'base_id', 'table_name']);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->app->getName(),
        );
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    AirtableApplication::BASE_ID    => self::BASE_ID,
                    AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                    ApplicationInterface::TOKEN     => self::API_KEY,
                ],
            ],
        );
        self::assertEquals(TRUE, $this->app->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testNoToken(): void
    {
        $applicationInstall = DataProvider::getBasicAppInstall(
            $this->app->getName(),
        );
        $applicationInstall->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    AirtableApplication::BASE_ID    => self::BASE_ID,
                    AirtableApplication::TABLE_NAME => self::TABLE_NAME,
                ],
            ],
        );
        $this->expectException(AuthorizationException::class);
        $this->app->getRequestDto(new ProcessDto(), $applicationInstall, 'POST');
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = self::getContainer()->get('hbpf.application.airtable');
    }

}
