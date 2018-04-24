<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppDeleteMapper;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\TestCase;

/**
 * Class SalesforceAppDeleteMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
final class SalesforceAppDeleteMapperTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function testProcessFailedMail(): void
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
    public function testProcessFailedList(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(2);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $mapper->process($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessFailedCreateDate(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(3);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $mapper->process($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessFailedUpdatedDate(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(4);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $mapper->process($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessFailedDeletedDate(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(5);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $mapper->process($dto);
    }

    /**
     * @throws Exception
     */
    public function testProcessSkip(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(6);

        $result = $mapper->process($dto);
        self::assertNotEmpty($result->getHeaders());
        self::assertArrayHasKey(CMHeaders::createKey(CMHeaders::RESULT_CODE), $result->getHeaders());
        self::assertEquals($result->getHeaders()[CMHeaders::createKey(CMHeaders::RESULT_CODE)], 1003);
    }

    /**
     * @throws Exception
     */
    public function testProcess(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(10);

        $result = $mapper->process($dto);

        $expected = '{"email":"kakin@athenahome.com","reactivate":true,"send_optin":false,"first_name":"Kristen","last_name":"Akin","lists":["b00caeeb-b2fe-79d8-8453-242f09b7c7f7"]}';
        self::assertEmpty($result->getHeaders());
        self::assertEquals($expected, $result->getData());
    }

    /**
     * ------------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @return SalesforceAppDeleteMapper
     */
    private function getMapper(): SalesforceAppDeleteMapper
    {
        $mapper = new SalesforceAppDeleteMapper();

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
                return file_get_contents(__DIR__ . '/data/mailFailed.json');
            case 2:
                return file_get_contents(__DIR__ . '/data/listFailed.json');
            case 3:
                return file_get_contents(__DIR__ . '/data/createFailed.json');
            case 4:
                return file_get_contents(__DIR__ . '/data/updateFailed.json');
            case 5:
                return file_get_contents(__DIR__ . '/data/deleteFailed.json');
            case 6:
                return file_get_contents(__DIR__ . '/data/ok.json');
            default:
                return file_get_contents(__DIR__ . '/data/deleteOk.json');
        }
    }

}