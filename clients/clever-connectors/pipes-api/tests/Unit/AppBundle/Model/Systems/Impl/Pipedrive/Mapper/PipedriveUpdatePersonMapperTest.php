<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper\PipedriveUpdatePersonMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveUpdatePersonMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
final class PipedriveUpdatePersonMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProccess(): void
    {
        $data = [
            CleverFieldsEnum::FOREIGN_ID => 'pid',
        ];

        $conn = new PipedriveUpdatePersonMapper($this->mockDM());
        $res  = $conn->process((new ProcessDto())->setData(json_encode($data))->setHeaders([
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE,
        ]));

        self::assertEquals([
            'id'   => 'pid',
            'body' => json_encode([
                'hrd' => 'true',
            ]),
        ], json_decode($res->getData(), TRUE));
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDM()
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            CleverCustomKeysEnum::UNSUBSCRIBE => 'un',
            CleverCustomKeysEnum::HARD_BOUNCE => 'hrd',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

}