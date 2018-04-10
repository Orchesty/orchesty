<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.4.18
 * Time: 17:38
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppCampaignsMapper;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\TestCase;

/**
 * Class SalesforceAppCampaignsMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper
 */
final class SalesforceAppCampaignsMapperTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testProcessFailed(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(1);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $mapper->process($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcess(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(10);

        $dto  = $mapper->process($dto);
        $data = json_decode($dto->getData(), TRUE);

        self::assertNotEmpty($data);
        self::assertArrayHasKey('results', $data);
        $data = $data['results'];

        self::assertArrayHasKey(0, $data);
        self::assertArrayHasKey(1, $data);
        $item = $data[1];

        self::assertArrayHasKey(SalesforceAppCampaignsMapper::NAME, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::ID, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::STATUS, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::SOURCE, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::CREATED, $item);

        $item = $data[1];
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::NAME, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::ID, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::STATUS, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::SOURCE, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::CREATED, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::FROM, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::TO, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::C_RATE, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::O_RATE, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::CLICKS, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::CLICKS_U, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::DOMAIN, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::OPENS, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::OPENS_U, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::SENT, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::SPAM, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::SUB, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::U_DEL, $item);
        self::assertArrayHasKey(SalesforceAppCampaignsMapper::U_SUB, $item);
    }

    /**
     * ------------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @return SalesforceAppCampaignsMapper
     */
    private function getMapper(): SalesforceAppCampaignsMapper
    {
        $mapper = new SalesforceAppCampaignsMapper();

        return $mapper;
    }

    /**
     * @param int $case
     *
     * @return ProcessDto
     */
    private function getDto(int $case): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders([])
            ->setData($this->loadData($case));

        return $dto;
    }

    /**
     * @param int $case
     *
     * @return string
     */
    private function loadData(int $case): string
    {
        switch ($case) {
            case 1:
                return '{}';
            default:
                return file_get_contents(__DIR__ . '/data/campaign.json');
        }
    }

}