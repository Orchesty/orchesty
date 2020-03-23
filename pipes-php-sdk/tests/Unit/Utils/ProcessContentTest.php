<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Utils;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class ProcessContentTest
 *
 * @package PipesPhpSdkTests\Unit\Utils
 */
final class ProcessContentTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait::getContentByKey
     *
     * @throws Exception
     */
    public function testGetContentKey(): void
    {
        $content = new NullProcessContent();

        $content = $this->invokeMethod($content, 'getContentByKey', [new ProcessDto(), 'key', ['key' => 'something']]);

        self::assertEquals('something', $content);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait::getContentByKey
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait::getByKey
     *
     * @throws Exception
     */
    public function testGetContentKeyErr(): void
    {
        $content = new NullProcessContent();

        self::expectException(ConnectorException::class);
        $this->invokeMethod($content, 'getContentByKey', [new ProcessDto(), 'key']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait::checkRequiredContent
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait::getByKey
     *
     * @throws Exception
     */
    public function testCheckRequiredContent(): void
    {
        $content = new NullProcessContent();

        $result = $this->invokeMethod(
            $content,
            'checkRequiredContent',
            [(new ProcessDto())->setData('{"param1": "1"}'), ['param1']]
        );

        self::assertEquals(['param1' => '1'], $result);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait::getByKey
     *
     * @throws Exception
     */
    public function testGetByKeyNull(): void
    {
        $arr     = ['key' => ['key2' => 1]];
        $content = new NullProcessContent();

        $result = $this->invokeMethod(
            $content,
            'getByKey',
            [&$arr, 'key.key2']
        );

        self::assertEquals(1, $result);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait::getByKey
     *
     * @throws Exception
     */
    public function testGetByKey(): void
    {
        $arr     = ['key' => 1];
        $content = new NullProcessContent();

        $result = $this->invokeMethod(
            $content,
            'getByKey',
            [&$arr, 'preKey.key']
        );

        self::assertNull($result);
    }

}
