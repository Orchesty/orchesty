<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
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

}