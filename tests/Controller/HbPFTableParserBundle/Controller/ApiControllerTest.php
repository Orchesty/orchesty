<?php declare(strict_types=1);

namespace Tests\Controller\HbPFTableParserBundle\Controller;

use Hanaboso\CommonsBundle\Exception\FileStorageException;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ApiControllerTest
 *
 * @package Tests\Controller\HbPFTableParserBundle\Controller
 */
final class ApiControllerTest extends ControllerTestCaseAbstract
{

    /**
     *
     */
    public function testToJson(): void
    {
        $response = $this->sendPost('/parser/csv/to/json', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/input-10.csv', __DIR__),
        ]);

        self::assertEquals(200, $response->status);
        self::assertEquals(
            json_decode((string) file_get_contents(__DIR__ . '/../../../Integration/Parser/data/output-10.json')),
            $response->content
        );
    }

    /**
     *
     */
    public function testToJsonNotFound(): void
    {
        $response = $this->sendPost('/parser/csv/to/json', ['file_id' => '']);

        self::assertEquals(500, $response->status);
        $content = $response->content;
        self::assertEquals(FileStorageException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     *
     */
    public function testToJsonTest(): void
    {
        $response = $this->sendGet('/parser/csv/to/json/test');

        self::assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testFromJson(): void
    {
        $response = $this->sendPost('/parser/json/to/csv', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
        ]);

        self::assertEquals(200, $response->status);
        self::assertRegExp('#\/tmp\/\d+\.\d+\.csv#i', $response->content);
    }

    /**
     *
     */
    public function testToFromNotFound(): void
    {
        $response = $this->sendPost('/parser/json/to/csv', ['file_id' => '']);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(FileStorageException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     *
     */
    public function testFromJsonNotFoundWriter(): void
    {
        $response = $this->sendPost('/parser/json/to/unknown', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
        ]);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TableParserException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     *
     */
    public function testFromTestJson(): void
    {
        $response = $this->sendGet('/parser/json/to/csv/test');

        self::assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testFromTestJsonNotFound(): void
    {
        $response = $this->sendGet('/parser/json/to/csv/test');

        self::assertEquals(200, $response->status);
    }

    /**
     *
     */
    public function testFromJsonTestNotFoundWriter(): void
    {
        $response = $this->sendPost('/parser/json/to/unknown', [
            'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
        ]);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TableParserException::class, $content->type);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     * @param string     $url
     * @param array      $parameters
     * @param array|null $content
     *
     * @return object
     */
    protected function sendPost(string $url, array $parameters, ?array $content = NULL): object
    {
        $this->client->request('POST', $url, $parameters, [], [], $content ? (string) json_encode($content) : '');
        $response = $this->client->getResponse();

        $res = json_decode($response->getContent(), TRUE);

        if (isset($res['error_code'])) {
            return parent::sendPost($url, $parameters, $content);
        }

        return (object) [
            'status'  => $response->getStatusCode(),
            'content' => json_decode($response->getContent()),
        ];
    }

}
