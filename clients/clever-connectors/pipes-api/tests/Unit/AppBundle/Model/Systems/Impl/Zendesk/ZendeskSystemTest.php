<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\ZendeskSystem;
use DateTime;
use Exception;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use PHPUnit\Framework\TestCase;

/**
 * Class ZendeskSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk
 */
final class ZendeskSystemTest extends TestCase
{

    /**
     * @var ZendeskSystem
     */
    private $system;

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

        $this->system        = new ZendeskSystem();
        $this->systemInstall = new SystemInstall();
        $this->systemInstall
            ->setUser('user123')
            ->setSystem('sys123')
            ->setSettings([
                SystemInstall::SYSTEM_LIMITS => [
                    SystemInstall::SYSTEM_LIMIT_VALUE  => 400,
                    SystemInstall::SYSTEM_LIMIT_UPDATE => new DateTime(),
                ],
            ]);
    }

    /**
     *
     */
    public function testGetLimit(): void
    {
        $systemLimit = $this->system->getLimit($this->systemInstall);

        $this->assertEquals([
            PipesHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER)   => 'sys123|user123',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER)  => '60',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER) => '400',
            SystemLimitDto::LIMIT_LAST_UPDATE                           => $this->systemInstall->getSettings()[SystemInstall::SYSTEM_LIMITS][SystemInstall::SYSTEM_LIMIT_UPDATE],
        ], $systemLimit->toArray());
    }

    /**
     * @throws Exception
     */
    public function testSaveLimit(): void
    {
        $data = [
            'X-Rate-Limit' => [
                0 => 400,
            ],
        ];

        $systemInstall = $this->system->saveLimit($this->systemInstall, $data);
        $systemLimits  = $systemInstall->getSettings()[SystemInstall::SYSTEM_LIMITS];

        $this->assertEquals(400, $systemLimits[SystemInstall::SYSTEM_LIMIT_VALUE]);
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

}