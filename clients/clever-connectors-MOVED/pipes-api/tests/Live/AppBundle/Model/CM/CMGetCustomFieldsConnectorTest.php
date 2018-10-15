<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\CustomFieldsConnector\CMGetCustomFieldsConnector;
use Exception;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class CMGetCustomFieldsConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\CM
 */
final class CMGetCustomFieldsConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testBatchAction(): void
    {
         $this->markTestSkipped('Online test');

        $dto = (new ProcessDto())->setHeaders([
            'pf-guid'       => '5a8b121f-a74c-11e7-a177-000d3a20eb16',
            'pf-token'      => '+-cl2-3FR-6FD_83L+_19X6+hbZrtfeI',
            'pf-system-key' => 'salesforceapp',
        ]);

        $system = new SystemInstall();
        $system
            ->setUser('5a8b121f-a74c-11e7-a177-000d3a20eb16')
            ->setToken('+-cl2-3FR-6FD_83L+_19X6+hbZrtfeI')
            ->setSystem('salesforceapp')
            ->setSettings([]);

        $this->persistAndFlush($system);

        $dtoData = [
            'system_install' => [
                '_id'               => $system->getId(),
                'user'              => $system->getUser(),
                'token'             => $system->getToken(),
                'system'            => $system->getSystem(),
                'encryptedSettings' => CryptManager::encrypt([]),
            ],
        ];

        $dto->setData(Json::encode($dtoData));

        $curl  = $this->container->get('cc.transport.curl.manager');
        $conn  = new CMGetCustomFieldsConnector($curl);
        $array = $conn->getCustomFieldsArray($dto);

        self::assertTrue(is_array($array));
        self::assertNotEmpty($array);
        $item = reset($array);
        self::assertArrayHasKey(CMGetCustomFieldsConnector::FIELD_ID, $item);
        self::assertArrayHasKey(CMGetCustomFieldsConnector::NAME, $item);
    }

}