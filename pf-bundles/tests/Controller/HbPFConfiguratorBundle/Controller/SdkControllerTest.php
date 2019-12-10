<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Exception;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\ControllerTestCaseAbstract;

/**
 * Class SdkControllerTest
 *
 * @package Tests\Controller\HbPFConfiguratorBundle\Controller
 */
final class SdkControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers SdkController::getAllAction
     * @covers SdkHandler::getAll
     * @covers SdkManager::getAll
     *
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->createSdk('One');
        $this->createSdk('Two');

        self::$client->request('GET', '/api/sdks');

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = Json::decode((string) $response->getContent());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('One', $content['items'][0]['key']);
        self::assertEquals('One', $content['items'][0]['value']);
        self::assertEquals('Two', $content['items'][1]['key']);
        self::assertEquals('Two', $content['items'][1]['value']);
    }

    /**
     * @covers SdkController::getOneAction
     * @covers SdkHandler::getOne
     * @covers SdkManager::getOne
     *
     * @throws Exception
     */
    public function testGetOne(): void
    {
        self::$client->request('GET', sprintf('/api/sdks/%s', $this->createSdk('One')->getId()));

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = Json::decode((string) $response->getContent());

        self::assertEquals('One', $content['key']);
        self::assertEquals('One', $content['value']);

        self::$client->request('GET', '/api/sdks/Unknown');

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = Json::decode((string) $response->getContent());

        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals(ControllerUtils::INTERNAL_SERVER_ERROR, $content['status']);
        self::assertEquals(DocumentNotFoundException::class, $content['type']);
        self::assertEquals("Document Sdk with key 'Unknown' not found!", $content['message']);
    }

    /**
     * @covers SdkController::createAction
     * @covers SdkHandler::create
     * @covers SdkManager::create
     *
     * @throws Exception
     */
    public function testCreate(): void
    {
        self::$client->request(
            'POST',
            '/api/sdks',
            [
                Sdk::KEY   => 'Key',
                Sdk::VALUE => 'Value',
            ]
        );

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = Json::decode((string) $response->getContent());

        $this->dm->clear();
        self::assertCount(1, $this->dm->getRepository(Sdk::class)->findAll());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Key', $content['key']);
        self::assertEquals('Value', $content['value']);
    }

    /**
     * @covers SdkController::updateAction
     * @covers SdkHandler::update
     * @covers SdkManager::update
     * @throws Exception
     */
    public function testUpdate(): void
    {
        self::$client->request(
            'PUT',
            sprintf('/api/sdks/%s', $this->createSdk('One')->getId()),
            [
                Sdk::KEY   => 'Key',
                Sdk::VALUE => 'Value',
            ]
        );

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = Json::decode((string) $response->getContent());

        $this->dm->clear();
        self::assertCount(1, $this->dm->getRepository(Sdk::class)->findAll());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Key', $content['key']);
        self::assertEquals('Value', $content['value']);
    }

    /**
     * @covers SdkController::deleteAction
     * @covers SdkHandler::delete
     * @covers SdkManager::delete
     *
     * @throws Exception
     */
    public function testDelete(): void
    {
        self::$client->request('DELETE', sprintf('/api/sdks/%s', $this->createSdk('One')->getId()));

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = Json::decode((string) $response->getContent());

        $this->dm->clear();
        self::assertCount(0, $this->dm->getRepository(Sdk::class)->findAll());

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('One', $content['key']);
        self::assertEquals('One', $content['value']);
    }

    /**
     * @param string $string
     *
     * @return Sdk
     * @throws Exception
     */
    private function createSdk(string $string): Sdk
    {
        $sdk = (new Sdk())
            ->setKey($string)
            ->setValue($string);

        $this->dm->persist($sdk);
        $this->dm->flush();

        return $sdk;
    }

}
