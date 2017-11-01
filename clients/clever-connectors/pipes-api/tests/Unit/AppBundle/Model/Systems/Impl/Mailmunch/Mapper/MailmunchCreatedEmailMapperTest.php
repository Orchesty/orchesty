<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Mailmunch\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class MailmunchCreatedEmailMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Mailmunch\Mapper
 */
class MailmunchCreatedEmailMapperTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $data = 'last-name=&first-name=sdf&email=asd%40asd.com&site-id=432743&form-id=559774&form-name=jgn&referral=http%3A%2F%2F194.213.36.182%2F&ip-address=188.122.212.69';
        $dto  = new ProcessDto();
        $dto->setData($data);

        $mapper = $this->container->get('hbpf.custom_node.mailmunch-created-email-mapper');
        $res    = $mapper->process($dto);

        $expt = [
            CleverFieldsEnum::EMAIL       => 'asd@asd.com',
            CleverFieldsEnum::FIRST_NAME  => 'sdf',
            CleverFieldsEnum::REACTIVATE  => TRUE,
        ];

        self::assertEquals($expt, json_decode($res->getData(), TRUE));
    }

}