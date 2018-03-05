<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 27.11.17
 * Time: 8:43
 */

namespace Tests\Integration\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use DateTime;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class UniversalMapperNodeTest
 *
 * @package Tests\Integration\AppBundle\Model\CustomNode
 */
final class UniversalMapperNodeTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \CleverConnectors\AppBundle\Model\CustomNode\UniversalMapperNode::process()
     * @covers \CleverConnectors\AppBundle\Model\CustomNode\UniversalMapperNode::getMapTemplate()
     */
    public function testProcess(): void
    {
        $topologyName  = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, 'null.user.group');
        $systemInstall = $this->getSysInstall();
        $date          = new DateTime('2016-02-26T00:00:00+01:00');
        $data          = [
            'string'    => 'string',
            'uri'       => 'http://example.com',
            'date_from' => $date->format(DateTime::W3C),
            'boolean'   => TRUE,
            'int'       => 10101,
        ];

        $this->getMap($systemInstall, $topologyName);
        $dto = new ProcessDto();
        $dto
            ->setData(json_encode($data))
            ->setHeaders($this->getHeaders($topologyName));

        $mapper = $this->container->get('hbpf.custom_node.universal-mapper');
        $dto    = $mapper->process($dto);
        $data   = json_decode($dto->getData(), TRUE);

        self::assertTrue(is_array($data));
        self::assertArrayHasKey(TypeEnum::TEXT, $data);
        self::assertArrayHasKey(TypeEnum::URL, $data);
        self::assertArrayHasKey(TypeEnum::DATE, $data);
        self::assertArrayHasKey(TypeEnum::BOOL, $data);
        self::assertArrayHasKey(TypeEnum::NUMBER, $data);

        self::assertEquals('string', $data[TypeEnum::TEXT]);
        self::assertEquals('http://example.com', $data[TypeEnum::URL]);
        self::assertEquals($date->format(DateTime::ISO8601), $data[TypeEnum::DATE]);
        self::assertEquals(TRUE, $data[TypeEnum::BOOL]);
        self::assertEquals(10101, $data[TypeEnum::NUMBER]);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\CustomNode\UniversalMapperNode::process()
     * @covers \CleverConnectors\AppBundle\Model\CustomNode\UniversalMapperNode::getMapTemplate()
     */
    public function testProcessActionNotExist(): void
    {
        $topologyName  = 'fake.name';
        $systemInstall = $this->getSysInstall();
        $date          = new DateTime('2016-02-26T00:00:00+01:00');
        $data          = [
            'string'    => 'string',
            'uri'       => 'http://example.com',
            'date_from' => $date->format(DateTime::W3C),
            'boolean'   => TRUE,
            'int'       => 10101,
        ];

        $this->getMap($systemInstall, $topologyName);
        $dto = new ProcessDto();
        $dto
            ->setData(json_encode($data))
            ->setHeaders($this->getHeaders($topologyName));

        $mapper = $this->container->get('hbpf.custom_node.universal-mapper');

        $processedDto = $mapper->process($dto);
        self::assertEquals($dto, $processedDto);
    }

    /**
     * ---------------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @param string $topologyName
     *
     * @return array
     */
    private function getHeaders(string $topologyName): array
    {
        $headers = [
            CMHeaders::createKey(CMHeaders::TOKEN)         => '123',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY)    => 'null.user',
            CMHeaders::createKey(CMHeaders::GUID)          => 'usr',
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => $topologyName,
        ];

        return $headers;
    }

    /**
     * @return SystemInstall
     */
    private function getSysInstall(): SystemInstall
    {
        $systemInstall = new SystemInstall();
        $systemInstall
            ->setSystem('null.user')
            ->setUser('usr')
            ->setToken('123');
        $this->persistAndFlush($systemInstall);

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $topologyName
     *
     * @return MapTemplate
     */
    private function getMap(SystemInstall $systemInstall, string $topologyName): MapTemplate
    {
        $textField = new MapField(TypeEnum::TEXT, new TypeEnum(TypeEnum::TEXT));
        $textField->addItem('string');

        $urlField = new MapField(TypeEnum::URL, new TypeEnum(TypeEnum::URL));
        $urlField->addItem('uri');

        $dateField = new MapField(TypeEnum::DATE, new TypeEnum(TypeEnum::DATE));
        $dateField->addItem('date_from');

        $boolField = new MapField(TypeEnum::BOOL, new TypeEnum(TypeEnum::BOOL));
        $boolField->addItem('boolean');

        $numField = new MapField(TypeEnum::NUMBER, new TypeEnum(TypeEnum::NUMBER));
        $numField->addItem('int');

        $actionDto = new ActionDto($topologyName, MapTemplate::DIRECTION_IN);

        $map = new MapTemplate();
        $map
            ->setSystemInstall($systemInstall)
            ->setAction($actionDto)
            ->setDirection($actionDto)
            ->addField($textField)
            ->addField($urlField)
            ->addField($dateField)
            ->addField($boolField)
            ->addField($numField);
        $this->persistAndFlush($map);

        return $map;
    }

}