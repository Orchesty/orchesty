<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Mapper\WisepopsCreatedEmailMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class WisepopsCreatedEmailMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Mapper
 */
final class WisepopsCreatedEmailMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers WisepopsCreateEmailMapper::process()
     */
    public function testProcessEvent(): void
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            SystemInstall::FORMS => [
                [
                    'form_id' => 99826,
                    'list'    => 'someList',
                ],
            ],
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        $connector = new WisepopsCreatedEmailMapper($dm);

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('WisepopsCreatedEmailItem.json'))
                ->setHeaders([]))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'sfg@sfd.cfg',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['someList'],
        ], $response);
    }

}