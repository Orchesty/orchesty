<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\CustomFieldsConnector\CMGetCustomFieldsConnector;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAuthConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use DateTime;
use Exception;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\StartingPointHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class SalesforceAppSystem
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp
 */
final class SalesforceAppSystemTest extends TestCase
{

    private const ACCESS_TOKEN = 'sdf5sd46';

    /**
     * @var SalesforceAppSystem|null
     */
    private $system = NULL;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!$this->system) {
            /** @var OAuth2Provider|MockObject $provider */
            $provider = $this->getMockBuilder(OAuth2Provider::class)->disableOriginalConstructor()->getMock();
            $provider->method('authorize')->willReturn(TRUE);

            /** @var SalesforceAuthConnector|MockObject $authConnector */
            $authConnector = $this->getMockBuilder(SalesforceAuthConnector::class)
                ->disableOriginalConstructor()
                ->getMock();
            $authConnector->method('sendAuthorizeConfirm')->willReturn(TRUE);

            /** @var StartingPointHandler|MockObject $pointHandler */
            $pointHandler = $this->getMockBuilder(StartingPointHandler::class)->disableOriginalConstructor()->getMock();
            $pointHandler->method('runWithRequest')->willReturn(TRUE);

            /** @var SystemLimitManager|MockObject $limitManager */
            $limitManager = $this->getMockBuilder(SystemLimitManager::class)->disableOriginalConstructor()->getMock();
            $limitManager->method('addSystemLimitToRequestHeaders')->willReturn(TRUE);

            /** @var CMGetCustomFieldsConnector|MockObject $fieldConnector */
            $fieldConnector = $this->getMockBuilder(CMGetCustomFieldsConnector::class)
                ->disableOriginalConstructor()
                ->getMock();
            $fieldConnector->method('getCustomFieldsArray')->willReturn([]);

            $this->system = new SalesforceAppSystem(
                $provider,
                $authConnector,
                $pointHandler,
                $limitManager,
                $fieldConnector
            );

            $this->systemInstall = new SystemInstall();
            $this->systemInstall
                ->setUser('user123')
                ->setSystem('sys123')
                ->setToken('tok123')
                ->setSettings([
                    'instance_url'               => 'systemUrl',
                    'access_token'               => self::ACCESS_TOKEN,
                    SystemInstall::SYSTEM_LIMITS => [
                        SystemInstall::SYSTEM_LIMIT_VALUE  => 15000,
                        SystemInstall::SYSTEM_LIMIT_UPDATE => new DateTime(),
                    ],
                ]);
        }
    }

    /**
     *
     */
    public function testGetLimit(): void
    {
        $systemLimit = $this->system->getLimit($this->systemInstall);

        $this->assertEquals([
            PipesHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER)   => 'sys123|user123',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER)  => '86400',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER) => '15000',
            SystemLimitDto::LIMIT_LAST_UPDATE                           => $this->systemInstall->getSettings()[SystemInstall::SYSTEM_LIMITS][SystemInstall::SYSTEM_LIMIT_UPDATE],
        ], $systemLimit->toArray());
    }

    /**
     * @throws Exception
     */
    public function testSaveLimit(): void
    {
        $data = [
            'DailyApiRequests' => [
                'Max' => 15000,
            ],
        ];

        $systemInstall = $this->system->saveLimit($this->systemInstall, $data);
        $systemLimits  = $systemInstall->getSettings()[SystemInstall::SYSTEM_LIMITS];

        $this->assertEquals(15000, $systemLimits[SystemInstall::SYSTEM_LIMIT_VALUE]);
        $this->assertInstanceOf(DateTime::class, $systemLimits[SystemInstall::SYSTEM_LIMIT_UPDATE]);
    }

    /**
     * @throws Exception
     */
    public function testSaveLimitFail(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->system->saveLimit($this->systemInstall, []);
    }

    /**
     * @throws Exception
     */
    public function testRunFilterSync(): void
    {
        $data = $this->system->runFilterSync(
            $this->systemInstall,
            [SalesforceAppSystem::DL_ID => '123456', SalesforceAppSystem::FILTER_ID => '123456']
        );
        self::assertTrue(is_array($data));
    }

    /**
     * @throws Exception
     */
    public function testRunFilterSyncFailed(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->system->runFilterSync($this->systemInstall, []);
    }

    /**
     * @throws Exception
     */
    public function testRunFilterSyncFailed2(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->system->runFilterSync($this->systemInstall, [SalesforceAppSystem::DL_ID => '123456']);
    }

}