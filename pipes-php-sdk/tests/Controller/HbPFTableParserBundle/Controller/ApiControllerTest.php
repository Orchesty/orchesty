<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFTableParserBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Controller\TableParserController;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
use Hanaboso\PipesPhpSdk\Parser\TableParser;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use Throwable;

/**
 * Class ApiControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFTableParserBundle\Controller
 */
#[CoversClass(TableParserController::class)]
#[CoversClass(TableParserHandler::class)]
#[CoversClass(TableParser::class)]
final class ApiControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testToJson(): void
    {
        $response = $this->sendPost(
            '/parser/csv/to/json',
            [
                'file_id' => sprintf('%s/../../../Integration/Parser/data/input-10.csv', __DIR__),
            ],
        );

        self::assertEquals(200, $response->status);
        self::assertEquals(
            Json::decode(File::getContent(__DIR__ . '/../../../Integration/Parser/data/output-10.json')),
            $response->content,
        );
    }

    /**
     * @throws Exception
     */
    public function testToJsonActionErr(): void
    {
        $this->prepareTableParserErr('parseToJson', new Exception());

        $this->client->request('POST', '/parser/csv/to/json');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testToJsonActionErr2(): void
    {
        $this->prepareTableParserErr('parseToJson', new PipesFrameworkException());
        $this->client->request('POST', '/parser/csv/to/json');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testToJsonNotFound(): void
    {
        $response = $this->sendPost('/parser/csv/to/json', ['file_id' => '']);

        self::assertEquals(500, $response->status);
        $content = $response->content;
        self::assertEquals(TableParserHandlerException::class, $content->type);
        self::assertEquals(401, $content->errorCode);
    }

    /**
     * @throws Exception
     */
    public function testToJsonTest(): void
    {
        $response = $this->sendGet('/parser/csv/to/json/test');

        self::assertEquals(200, $response->status);
    }

    /**
     * @throws Exception
     */
    public function testToJsonTestErr(): void
    {
        $this->prepareTableParserErr('parseToJsonTest', new Exception());
        $response = $this->sendGet('/parser/csv/to/json/test');

        self::assertEquals(500, $response->status);
    }

    /**
     * @throws Exception
     */
    public function testFromJson(): void
    {
        $response = $this->sendPost(
            '/parser/json/to/csv',
            [
                'file_id' => __DIR__ . '/../../../Integration/Parser/data/output-10.json',
            ],
        );

        self::assertEquals(200, $response->status);
        self::assertMatchesRegularExpression('#\/tmp\/\d+\.\d+\.csv#i', $response->content);
    }

    /**
     * @throws Exception
     */
    public function testToFromNotFound(): void
    {
        $response = $this->sendPost('/parser/json/to/csv', ['file_id' => '']);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TableParserHandlerException::class, $content->type);
        self::assertEquals(401, $content->errorCode);
    }

    /**
     * @throws Exception
     */
    public function testFromJsonNotFoundWriter(): void
    {
        $response = $this->sendPost(
            '/parser/json/to/unknown',
            [
                'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
            ],
        );
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TableParserException::class, $content->type);
        self::assertEquals(801, $content->errorCode);
    }

    /**
     * @throws Exception
     */
    public function testFromTestJson(): void
    {
        $response = $this->sendGet('/parser/json/to/csv/test');

        self::assertEquals(200, $response->status);
    }

    /**
     * @throws Exception
     */
    public function testFromTestJsonNotFound(): void
    {
        $response = $this->sendGet('/parser/json/to/csv/test');

        self::assertEquals(200, $response->status);
    }

    /**
     * @throws Exception
     */
    public function testFromTestJsonNotFoundErr(): void
    {
        $this->prepareTableParserErr('parseFromJsonTest', new TableParserException());
        $response = $this->sendGet('/parser/json/to/csv/test');

        self::assertEquals(500, $response->status);
    }

    /**
     * @throws Exception
     */
    public function testFromJsonTestNotFoundWriter(): void
    {
        $response = $this->sendPost(
            '/parser/json/to/unknown',
            [
                'file_id' => sprintf('%s/../../../Integration/Parser/data/output-10.json', __DIR__),
            ],
        );
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TableParserException::class, $content->type);
        self::assertEquals(801, $content->errorCode);
    }

    /**
     * @param string       $url
     * @param mixed[]      $parameters
     * @param mixed[]|null $content
     *
     * @return object
     * @throws Exception
     */
    protected function sendPost(string $url, array $parameters, ?array $content = NULL): object
    {
        $this->client->request(
            'POST',
            $url,
            $parameters,
            [],
            [],
            $content ? Json::encode($content) : '',
        );

        $response = $this->client->getResponse();

        try {
            $res = Json::decode((string) $response->getContent());

            if (isset($res['error_code'])) {
                return parent::sendPost($url, $parameters, $content);
            }

            return (object) [
                'content' => Json::decode((string) $response->getContent()),
                'status'  => $response->getStatusCode(),
            ];
        } catch (Throwable $e) {
            $e;

            return (object) [
                'content' => (string) $response->getContent(),
                'status'  => $response->getStatusCode(),
            ];
        }
    }

    /**
     * @param string $methodName
     * @param mixed  $returnValue
     *
     * @throws Exception
     */
    private function prepareTableParserErr(string $methodName, mixed $returnValue): void
    {
        $mapperHandlerMock = self::createMock(TableParserHandler::class);
        $mapperHandlerMock
            ->method($methodName)
            ->willThrowException($returnValue);

        $container = $this->client->getContainer();
        $container->set('hbpf.parser.table.handler', $mapperHandlerMock);
    }

}
