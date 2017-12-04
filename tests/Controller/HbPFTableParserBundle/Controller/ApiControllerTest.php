<?php declare(strict_types=1);

namespace Tests\Controller\HbPFTableParserBundle\Controller;

use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ApiControllerTest
 *
 * @package Tests\Controller\HbPFTableParserBundle\Controller
 */
class ApiControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testToJson(): void
    {
        $response = $this->sendPost('/parser/csv/to/json', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/input-10.csv', __DIR__),
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertEquals(
            file_get_contents(sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__)),
            $response->content
        );
    }

    /**
     *
     */
    public function testToJsonNotFound(): void
    {
        $response = $this->sendPost('/parser/csv/to/json', ['file_id' => '']);

        $this->assertEquals(500, $response->status);
        $content = $response->content;
        $this->assertEquals(FileStorageException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testToJsonTest(): void
    {
        $response = $this->sendPost('/parser/csv/to/json/test', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/input-10.csv', __DIR__),
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertTrue($response->content);
    }

    /**
     *
     */
    public function testFromJson(): void
    {
        $response = $this->sendPost('/parser/json/to/csv', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertRegExp('#\/tmp\/\d+\.\d+\.csv#i', $response->content);
    }

    /**
     *
     */
    public function testToFromNotFound(): void
    {
        $response = $this->sendPost('/parser/json/to/csv', ['file_id' => '']);
        $content = $response->content;

        $this->assertEquals(500, $response->status);
        $this->assertEquals(FileStorageException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testFromJsonNotFoundWriter(): void
    {
        $response = $this->sendPost('/parser/json/to/unknown', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
        ]);
        $content = $response->content;

        $this->assertEquals(500, $response->status);
        $this->assertEquals(TableParserException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

    /**
     *
     */
    public function testFromTestJson(): void
    {
        $response = $this->sendPost('/parser/json/to/csv/test', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
        ]);

        $this->assertEquals(200, $response->status);
        $this->assertTrue($response->content);
    }

    /**
     *
     */
    public function testFromTestJsonNotFound(): void
    {
        $response = $this->sendPost('/parser/json/to/csv/test', ['file_id' => '',]);

        $this->assertEquals(200, $response->status);
        $this->assertTrue($response->content);
    }

    /**
     *
     */
    public function testFromJsonTestNotFoundWriter(): void
    {
        $response = $this->sendPost('/parser/json/to/unknown', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
        ]);
        $content = $response->content;

        $this->assertEquals(500, $response->status);
        $this->assertEquals(TableParserException::class, $content->type);
        $this->assertEquals(2001, $content->error_code);
    }

}