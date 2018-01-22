<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use DateTime;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class SalesforceSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce
 */
final class SalesforceSystemTest extends KernelTestCaseAbstract
{

    private const ACCESS_TOKEN = 'sdf5sd46';

    /**
     * @var SalesforceSystem|null
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

            $this->system = new SalesforceSystem($provider);

            $this->systemInstall = new SystemInstall();
            $this->systemInstall
                ->setUser('user123')
                ->setSystem('sys123')
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
            PipesHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER)   => 'user123-sys123',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER)  => '86400',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER) => '15000',
            SystemLimitDto::LIMIT_LAST_UPDATE                           => $this->systemInstall->getSettings()[SystemInstall::SYSTEM_LIMITS][SystemInstall::SYSTEM_LIMIT_UPDATE],
        ], $systemLimit->toArray());
    }

    /**
     *
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
     *
     */
    public function testSaveLimitFail(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->system->saveLimit($this->systemInstall, []);
    }

}