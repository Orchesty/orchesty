<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper\BasecrmCreatedContactMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmCreatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
final class BasecrmCreatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'list' => 'someList',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        $node = new BasecrmCreatedContactMapper($dm);

        $response = Json::decode($node->process(
            (new ProcessDto())->setData(
                $this->getRequest('contactItem.json')
            )->setHeaders([]))->getData(), TRUE
        );

        $expt = [
            'email'       => 'asd@asd.com',
            'first_name'  => 'Base',
            '_foreign_id' => '187596661',
            'reactivate'  => TRUE,
            'send_optin'  => FALSE,
            'lists'       => ['someList'],
        ];

        self::assertEquals($expt, $response);
    }

}