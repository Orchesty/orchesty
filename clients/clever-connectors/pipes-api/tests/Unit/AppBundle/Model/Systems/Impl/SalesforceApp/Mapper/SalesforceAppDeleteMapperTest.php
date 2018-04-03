<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppDeleteMapper;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppMapperAbstract;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
        $dto    = $this->getDto();

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
        $dto    = $this->getDto([SalesforceAppMapperAbstract::EMAIL => 'aaa@bbb.ccc']);

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
        $dto    = $this->getDto([
            SalesforceAppMapperAbstract::EMAIL => 'aaa@bbb.ccc',
            SalesforceAppMapperAbstract::LIST  => '12345679',
        ]);

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
        $dto    = $this->getDto([
            SalesforceAppMapperAbstract::EMAIL   => 'aaa@bbb.ccc',
            SalesforceAppMapperAbstract::LIST    => '12345679',
            SalesforceAppMapperAbstract::CREATED => '2018-02-22T14:18:06.000+0000',
        ]);

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
        $dto    = $this->getDto([
            SalesforceAppMapperAbstract::EMAIL   => 'aaa@bbb.ccc',
            SalesforceAppMapperAbstract::LIST    => '12345679',
            SalesforceAppMapperAbstract::CREATED => '2018-02-22T14:18:06.000+0000',
            SalesforceAppMapperAbstract::UPDATED => '2018-02-22T14:18:06.000+0000',
        ]);

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
        $dto    = $this->getDto([
            SalesforceAppMapperAbstract::EMAIL   => 'aaa@bbb.ccc',
            SalesforceAppMapperAbstract::LIST    => '12345679',
            SalesforceAppMapperAbstract::CREATED => '2018-02-22T14:18:06.000+0000',
            SalesforceAppMapperAbstract::UPDATED => '2018-02-22T14:18:06.000+0000',
            SalesforceAppMapperAbstract::DELETED => FALSE,
        ]);

        $result = $mapper->process($dto);
        self::assertNotEmpty($result->getHeaders());
        self::assertArrayHasKey(CMHeaders::createKey(CMHeaders::RESULT_CODE), $result->getHeaders());
        self::assertEquals($result->getHeaders()[CMHeaders::createKey(CMHeaders::RESULT_CODE)], 1003);
    }

    /**
     * @throws Exception
     */
    public function testProcessSkip2(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto([
            SalesforceAppMapperAbstract::EMAIL   => 'aaa@bbb.ccc',
            SalesforceAppMapperAbstract::LIST    => '12345679',
            SalesforceAppMapperAbstract::CREATED => '2018-02-22T14:18:06.000+0000',
            SalesforceAppMapperAbstract::UPDATED => '2018-02-22T14:18:06.000+0000',
            SalesforceAppMapperAbstract::DELETED => FALSE,
        ]);

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
        $dto    = $this->getDto([
            SalesforceAppMapperAbstract::EMAIL   => 'aaa@bbb.ccc',
            SalesforceAppMapperAbstract::LIST    => '12345679',
            SalesforceAppMapperAbstract::CREATED => '2018-02-22T14:18:06.000+0000',
            SalesforceAppMapperAbstract::UPDATED => '2018-02-22T15:18:06.000+0000',
            SalesforceAppMapperAbstract::DELETED => TRUE,
        ]);

        $result = $mapper->process($dto);

        $expected = '{"email":"aaa@bbb.ccc","reactivate":true,"send_optin":false,"lists":["12345679"]}';
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
     * @param array $data
     *
     * @return ProcessDto
     */
    private function getDto(array $data = []): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders([])
            ->setData(json_encode($data));

        return $dto;
    }

}