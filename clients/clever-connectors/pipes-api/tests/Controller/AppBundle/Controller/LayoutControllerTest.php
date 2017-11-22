<?php declare(strict_types=1);

namespace Tests\Controller\AppBundle\Controller;

use CleverConnectors\AppBundle\Controller\LayoutController;
use CleverConnectors\AppBundle\Document\DataLayout;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\DataLayout\LayoutField;
use Nette\Utils\Json;
use Tests\ControllerTestCaseAbstract;

/**
 * Class LayoutControllerTest
 *
 * @package Tests\Controller\AppBundle\Controller
 */
final class LayoutControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers LayoutController::createAction()
     */
    public function testCreate(): void
    {
        $this->loginUser('user@example.com', 'pass');

        $system = (new SystemInstall())
            ->setUser('user123')
            ->setSystem('null.user.group')
            ->setToken('token123');
        $this->persistAndFlush($system);

        $params = [
            'action' => DataLayoutActionEnum::SUBSCRIBER,
            'fields' => [
                [
                    'key'  => 'abc',
                    'type' => TypeEnum::TEXT,
                ],
            ],
        ];

        $response = $this->sendPost('/layout/user/user123/system/null.user.group', $params);

        $layout = $this->dm->getRepository(DataLayout::class)->findOneBy([
            'systemInstall' => $system->getId(),
            'action'        => DataLayoutActionEnum::SUBSCRIBER,
        ]);

        $this->assertEquals(1, count($layout));
        $this->assertEquals(200, $response->status);

        $content = Json::decode(Json::encode($response->content), TRUE);
        $this->assertEquals($params, $content);
    }

    /**
     * @covers LayoutController::updateAction()
     */
    public function testUpdate(): void
    {
        $this->loginUser('user@example.com', 'pass');

        $system = (new SystemInstall())
            ->setUser('user123')
            ->setSystem('null.user.group')
            ->setToken('token123');
        $this->persistAndFlush($system);

        $params = [
            'fields' => [
                [
                    'key'  => 'abc',
                    'type' => TypeEnum::TEXT,
                ],
            ],
        ];

        $field  = new LayoutField('aaa', new TypeEnum(TypeEnum::BOOL));
        $layout = new DataLayout();
        $layout
            ->setSystemInstall($system)
            ->setAction(new DataLayoutActionEnum(DataLayoutActionEnum::CAMPAIGN))
            ->addField($field);
        $this->persistAndFlush($layout);

        $response = $this->sendPut(
            sprintf('/layout/%s/user/user123/system/null.user.group', $layout->getId()),
            $params
        );

        $this->dm->clear();

        $layout = $this->dm->getRepository(DataLayout::class)->find($layout->getId());

        $this->assertEquals(1, count($layout));
        $this->assertEquals(200, $response->status);

        $content = Json::decode(Json::encode($response->content), TRUE);
        $this->assertEquals(array_merge($params, ['action' => DataLayoutActionEnum::CAMPAIGN]), $content);
    }

    /**
     * @covers LayoutController::deleteAction()
     */
    public function testDelete(): void
    {
        $this->loginUser('user@example.com', 'pass');

        $system = (new SystemInstall())
            ->setUser('user123')
            ->setSystem('null.user.group')
            ->setToken('token123');
        $this->persistAndFlush($system);

        $field  = new LayoutField('aaa', new TypeEnum(TypeEnum::BOOL));
        $layout = new DataLayout();
        $layout
            ->setSystemInstall($system)
            ->setAction(new DataLayoutActionEnum(DataLayoutActionEnum::CAMPAIGN))
            ->addField($field);
        $this->persistAndFlush($layout);

        $response = $this->sendDelete(sprintf('/layout/%s/user/user123/system/null.user.group', $layout->getId()));

        $this->dm->clear();

        $layout = $this->dm->getRepository(DataLayout::class)->find($layout->getId());

        $this->assertNull($layout);
        $this->assertEquals(200, $response->status);
        $this->assertEquals([], $response->content);
    }

}