<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Tests\KernelTestCaseAbstract;

/**
 * Class AirtableSystemTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable
 */
class AirtableSystemTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetRequestDto(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setSettings([
                'api_key' => 'abc123',
            ]);

        $system = new AirtableSystem();
        $result = $system->getRequestDto($systemInstall, 'GET');

        $this->assertEquals('https://api.airtable.com/v0/', (string) $result->getUri());
        $this->assertEquals('GET', $result->getMethod());
        $this->assertEquals([
            'Authorization' => 'Bearer abc123',
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ], $result->getHeaders());
    }

    /**
     *
     */
    public function testSaveForms(): void
    {
        $sys = new AirtableSystem();

        $data = [
            [
                AirtableSystem::TABLE_URL => 'a',
                AirtableSystem::LIST_ID   => 'a',
            ],
            [
                AirtableSystem::TABLE_URL => 'a',
                AirtableSystem::LIST_ID   => 'a',
            ],
            [
                AirtableSystem::TABLE_URL => 'b',
                AirtableSystem::LIST_ID   => 'a',
            ],
        ];

        $systemInstall = new SystemInstall();
        $systemInstall->setToken('tkn');

        $res = $sys->saveCustomForm($systemInstall, $data);

        self::assertEquals(2, count($res[SystemInstall::FORMS]));
    }

    /**
     *
     */
    public function testGetLimitDefault(): void
    {
        $system        = new AirtableSystem();
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user123')
            ->setSystem('sys123')
            ->setSettings([
                'instance_url' => 'systemUrl',
                'access_token' => '123',
            ]);

        $systemLimit = $system->getLimit($systemInstall);

        $this->assertEquals([
            PipesHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER)   => 'sys123|user123',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER)  => 1,
            PipesHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER) => 5,
            SystemLimitDto::LIMIT_LAST_UPDATE                           => NULL,
        ], $systemLimit->toArray());
    }

    /**
     *
     */
    public function testGetLimit(): void
    {
        $system        = new AirtableSystem();
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user123')
            ->setSystem('sys123')
            ->setSettings([
                'instance_url'                    => 'systemUrl',
                'access_token'                    => '123',
                SystemInstall::SYSTEM_LIMIT_VALUE => 10,
            ]);

        $systemLimit = $system->getLimit($systemInstall);

        $this->assertEquals([
            PipesHeaders::createKey(SystemLimitDto::LIMIT_KEY_HEADER)   => 'sys123|user123',
            PipesHeaders::createKey(SystemLimitDto::LIMIT_TIME_HEADER)  => 1,
            PipesHeaders::createKey(SystemLimitDto::LIMIT_VALUE_HEADER) => 10,
            SystemLimitDto::LIMIT_LAST_UPDATE                           => NULL,
        ], $systemLimit->toArray());
    }

    /**
     *
     */
    public function testSaveLimit(): void
    {
        $system        = new AirtableSystem();
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setSettings([
                SystemInstall::SYSTEM_LIMIT_VALUE => 10,
            ]);

        $systemInstall = $system->saveLimit($systemInstall, []);

        $this->assertArrayHasKey(SystemInstall::SYSTEM_LIMIT_VALUE, $systemInstall->getSettings());
        $this->assertEquals(10, $systemInstall->getSettings()[SystemInstall::SYSTEM_LIMIT_VALUE]);
    }

}