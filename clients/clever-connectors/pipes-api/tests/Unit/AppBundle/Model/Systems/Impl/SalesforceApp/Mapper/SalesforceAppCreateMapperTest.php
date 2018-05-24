<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAppMapFieldsConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppCreateMapper;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper\SalesforceAppMapperAbstract;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Class SalesforceAppCreateMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
final class SalesforceAppCreateMapperTest extends TestCase
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
    public function testProcessSkip2(): void
    {
        $mapper = $this->getMapper();
        $dto    = $this->getDto(7);

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
     * @throws Exception
     */
    public function testProcessWithCustomFields(): void
    {
        $fields = [];

        for ($i = 1; $i < 11; $i++) {
            $fields[] = [
                SalesforceAppMapperAbstract::CM_FIELD  => (string) $i,
                SalesforceAppMapperAbstract::ID_CUSTOM => sprintf('CMHB__CustomField%s__c', $i),
            ];
        }

        $settings = [SalesforceAppMapFieldsConnector::MAP_FIELDS => $fields];

        $system = new SystemInstall();
        $system->setSettings($settings);

        $mapper = $this->getMapper($system);
        $dto    = $this->getDto(8);

        $result = $mapper->process($dto);

        $expected = '{"email":"kakin@athenahome.com","reactivate":true,"send_optin":false,"first_name":"Kristen","last_name":"Akin","lists":["b00caeeb-b2fe-79d8-8453-242f09b7c7f7"],"fields":[{"field_id":"1","values":["test1"]},{"field_id":"2","values":["test2"]},{"field_id":"3","values":["test3"]},{"field_id":"4","values":["test4"]},{"field_id":"5","values":["test5"]},{"field_id":"6","values":["test6"]},{"field_id":"7","values":["test7"]},{"field_id":"8","values":["test8"]},{"field_id":"9","values":["test9"]},{"field_id":"10","values":["test10"]}]}';
        self::assertEmpty($result->getHeaders());
        self::assertEquals($expected, $result->getData());
    }

    /**
     * ------------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @param SystemInstall|null $systemInstall
     *
     * @return SalesforceAppCreateMapper
     * @throws ReflectionException
     */
    private function getMapper(?SystemInstall $systemInstall = NULL): SalesforceAppCreateMapper
    {
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($systemInstall ?? new SystemInstall());

        /** @var DocumentManager|MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        $mapper = new SalesforceAppCreateMapper($dm);

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
                return file_get_contents(__DIR__ . '/data/createSkip.json');
            case 7:
                return file_get_contents(__DIR__ . '/data/createSkip2.json');
            case 8:
                return file_get_contents(__DIR__ . '/data/customFields.json');
            default:
                return file_get_contents(__DIR__ . '/data/ok.json');
        }
    }

}