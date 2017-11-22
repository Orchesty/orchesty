<?php declare(strict_types=1);

namespace Tests\Controller\AppBundle\Controller;

use CleverConnectors\AppBundle\Controller\MapController;
use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use Nette\Utils\Json;
use Tests\ControllerTestCaseAbstract;

/**
 * Class MapControllerTest
 *
 * @package Tests\Controller\AppBundle\Controller
 */
final class MapControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers MapController::createAction()
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
            'action'    => DataLayoutActionEnum::SUBSCRIBER,
            'direction' => MapTemplate::DIRECTION_IN,
            'fields'    => [
                [
                    'name'  => 'abc',
                    'type'  => TypeEnum::TEXT,
                    'items' => ['def', 'ghi'],
                ],
            ],
        ];

        $response = $this->sendPost('/map/user/user123/system/null.user.group', $params);

        $map = $this->dm->getRepository(MapTemplate::class)->findOneBy([
            'systemInstall' => $system->getId(),
            'action'        => DataLayoutActionEnum::SUBSCRIBER,
            'direction'     => MapTemplate::DIRECTION_IN,
        ]);

        $this->assertEquals(1, count($map));
        $this->assertEquals(200, $response->status);

        $content = Json::decode(Json::encode($response->content), TRUE);
        $this->assertEquals($params, $content);
    }

    /**
     * @covers MapController::updateAction()
     */
    public function testUpdate(): void
    {
        $this->loginUser('user@example.com', 'pass');

        $system = (new SystemInstall())
            ->setUser('user1234')
            ->setSystem('null.user.group')
            ->setToken('token1234');
        $this->persistAndFlush($system);

        $params = [
            'action'    => DataLayoutActionEnum::SUBSCRIBER,
            'direction' => MapTemplate::DIRECTION_IN,
            'fields'    => [
                [
                    'name'  => 'abc',
                    'type'  => TypeEnum::TEXT,
                    'items' => ['def', 'ghi'],
                ],
            ],
        ];

        $field = new MapField('aaa', new TypeEnum(TypeEnum::BOOL));
        $field->addItem('bbb');

        $map = new MapTemplate();
        $map
            ->setAction(new DataLayoutActionEnum(DataLayoutActionEnum::CAMPAIGN))
            ->setDirection(MapTemplate::DIRECTION_OUT)
            ->setSystemInstall($system)
            ->addField($field);
        $this->persistAndFlush($map);

        $response = $this->sendPut(sprintf('/map/%s/user/user1234/system/null.user.group', $map->getId()), $params);

        $this->dm->clear();

        $map = $this->dm->getRepository(MapTemplate::class)->find($map->getId());

        $this->assertEquals(1, count($map));
        $this->assertEquals(200, $response->status);

        $content = Json::decode(Json::encode($response->content), TRUE);
        $this->assertEquals($params, $content);
    }

    /**
     * @covers MapController::deleteAction()
     */
    public function testDelete(): void
    {
        $this->loginUser('user@example.com', 'pass');

        $system = (new SystemInstall())
            ->setUser('user12345')
            ->setSystem('null.user.group')
            ->setToken('token12345');
        $this->persistAndFlush($system);

        $field = new MapField('aaa', new TypeEnum(TypeEnum::BOOL));
        $field->addItem('bbb');

        $map = new MapTemplate();
        $map
            ->setAction(new DataLayoutActionEnum(DataLayoutActionEnum::CAMPAIGN))
            ->setDirection(MapTemplate::DIRECTION_OUT)
            ->setSystemInstall($system)
            ->addField($field);
        $this->persistAndFlush($map);

        $response = $this->sendDelete(sprintf('/map/%s/user/user12345/system/null.user.group', $map->getId()));

        $this->dm->clear();

        $map = $this->dm->getRepository(MapTemplate::class)->find($map->getId());

        $this->assertNull($map);
        $this->assertEquals(200, $response->status);
        $this->assertEquals([], $response->content);
    }

}