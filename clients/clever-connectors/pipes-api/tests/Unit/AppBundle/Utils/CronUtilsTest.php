<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/11/17
 * Time: 2:44 PM
 */

namespace Tests\Unit\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CronUtils;
use CleverConnectors\AppBundle\Utils\Dto\Times;
use DateTime;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\TestCase;

/**
 * Class CronUtilsTest
 *
 * @package Tests\Unit\AppBundle\Utils
 */
final class CronUtilsTest extends TestCase
{

    /**
     * @covers CronUtils::parseData()
     */
    public function testParseData(): void
    {
        $content = ['key' => 'val'];

        $dto = new ProcessDto();
        $dto->setData(json_encode($content));

        $res = CronUtils::parseData($dto);

        self::assertEquals($content, $res);
    }

    /**
     * @covers CronUtils::parseData()
     */
    public function testParseDataNoData(): void
    {
        $dto = new ProcessDto();
        $dto->setData(json_encode([]));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        CronUtils::parseData($dto);
    }

    /**
     * @covers CronUtils::parseData()
     */
    public function testParseDataInvalidData(): void
    {
        $dto = new ProcessDto();
        $dto->setData(json_encode(123));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        CronUtils::parseData($dto);
    }

    /**
     *
     */
    public function testGetSystemInstall(): void
    {
        $content = [
            'system_install' => [
                'user'   => 'username',
                'token'  => 'tokenstring',
                'system' => 'systemname',
            ],
        ];

        $dto = new ProcessDto();
        $dto->setData(json_encode($content));

        $res = CronUtils::getSystemInstall($dto);

        self::assertEquals($content['system_install']['user'], $res->getUser());
        self::assertEquals($content['system_install']['token'], $res->getToken());
        self::assertEquals($content['system_install']['system'], $res->getSystem());
    }

    /**
     *
     */
    public function testGetSystemInstallNoData(): void
    {
        $dto = new ProcessDto();
        $dto->setData(json_encode([]));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        CronUtils::getSystemInstall($dto);
    }

    /**
     *
     */
    public function testGetSystemInstallInvalidData(): void
    {
        $dto = new ProcessDto();
        $dto->setData(json_encode(123));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        CronUtils::getSystemInstall($dto);
    }

    /**
     *
     */
    public function testGetSystemInstallNoSystemData(): void
    {
        $dto = new ProcessDto();
        $dto->setData(json_encode([]));

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        CronUtils::getSystemInstall($dto);
    }

    /**
     *
     */
    public function testGetTimes1(): void
    {
        $res = CronUtils::getTimes(new LastSync());

        self::assertInstanceOf(Times::class, $res);
        self::assertNull($res->getStart());
        self::assertInstanceOf(DateTime::class, $res->getEnd());
    }

    /**
     *
     */
    public function testGetTimes2(): void
    {
        $time      = time();
        $timestamp = (new DateTime())->setTimestamp($time);
        $res       = CronUtils::getTimes((new LastSync())->setTimestamp($timestamp));

        self::assertInstanceOf(Times::class, $res);
        self::assertEquals($time, $res->getStart()->getTimestamp());
        self::assertInstanceOf(DateTime::class, $res->getEnd());
    }

}