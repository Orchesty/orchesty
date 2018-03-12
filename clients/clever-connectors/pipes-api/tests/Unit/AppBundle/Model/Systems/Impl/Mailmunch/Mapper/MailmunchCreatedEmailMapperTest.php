<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Mailmunch\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Mailmunch\Mapper\MailmunchCreatedEmailMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class MailmunchCreatedEmailMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Mailmunch\Mapper
 */
final class MailmunchCreatedEmailMapperTest extends KernelTestCaseAbstract
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

        $data = json_encode([
            'first-name' => 'first',
            'last-name'  => 'last',
            'email'      => 'asd@asd.com',
        ]);

        $dto = new ProcessDto();
        $dto->setData($data)->setHeaders([]);

        $mapper = new MailmunchCreatedEmailMapper($dm);
        $res    = $mapper->process($dto);

        $expt = [
            CleverFieldsEnum::EMAIL      => 'asd@asd.com',
            CleverFieldsEnum::FIRST_NAME => 'first',
            CleverFieldsEnum::LAST_NAME  => 'last',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['someList'],
        ];

        self::assertEquals($expt, json_decode($res->getData(), TRUE));
    }

}