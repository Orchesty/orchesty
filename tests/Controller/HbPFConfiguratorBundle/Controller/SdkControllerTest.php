<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Exception;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
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
     */
    public function testGetAll(): void
    {
        $this->createSdk('One');
        $this->createSdk('Two');

        self::$client->request('GET', '/api/sdks');

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('One', $content['items'][0]['key']);
        self::assertEquals('One', $content['items'][0]['value']);
        self::assertEquals('Two', $content['items'][1]['key']);
        self::assertEquals('Two', $content['items'][1]['value']);
    }

    /**
     * @throws Exception
     *
     * @covers SdkController::getOneAction
     * @covers SdkHandler::getOne
     * @covers SdkManager::getOne
     */
    public function testGetOne(): void
    {
        self::$client->request('GET', sprintf('/api/sdks/%s', $this->createSdk('One')->getId()));

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('One', $content['key']);
        self::assertEquals('One', $content['value']);

        self::$client->request('GET', '/api/sdks/Unknown');

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals(ControllerUtils::INTERNAL_SERVER_ERROR, $content['status']);
        self::assertEquals(DocumentNotFoundException::class, $content['type']);
        self::assertEquals("Document Sdk with key 'Unknown' not found!", $content['message']);
    }

    /**
     * @covers SdkController::createAction
     * @covers SdkHandler::create
     * @covers SdkManager::create
     */
    public function testCreate(): void
    {
        self::$client->request('POST', '/api/sdks', [
            Sdk::KEY   => 'Key',
            Sdk::VALUE => 'Value',
        ]);

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);

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
     */
    public function testUpdate(): void
    {
        self::$client->request('PUT', sprintf('/api/sdks/%s', $this->createSdk('One')->getId()), [
            Sdk::KEY   => 'Key',
            Sdk::VALUE => 'Value',
        ]);

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);

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
     */
    public function testDelete(): void
    {
        self::$client->request('DELETE', sprintf('/api/sdks/%s', $this->createSdk('One')->getId()));

        /** @var JsonResponse $response */
        $response = self::$client->getResponse();
        $content  = json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);

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
