<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper\PipedriveCreatePersonMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveCreatePersonMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
final class PipedriveCreatePersonMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProccess(): void
    {
        $data = [
            CleverFieldsEnum::EMAIL      => 'asd@asd.com',
            CleverFieldsEnum::FIRST_NAME => 'qwe',
            CleverFieldsEnum::LAST_NAME  => 'asd',
            CleverFieldsEnum::REACTIVATE => TRUE,
        ];

        $conn = new PipedriveCreatePersonMapper($this->mockDM(), $this->ownContainer->get('systems.pipedrive'));
        $res  = $conn->process((new ProcessDto())->setData(json_encode($data))->setHeaders([]));

        self::assertEquals([
            'email' => 'asd@asd.com',
            'name'  => 'qwe asd',
            'hrd'   => 'false',
            'un'    => 'false',
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