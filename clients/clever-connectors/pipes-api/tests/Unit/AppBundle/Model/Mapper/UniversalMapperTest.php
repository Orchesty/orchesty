<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.11.17
 * Time: 15:49
 */

namespace Tests\Unit\AppBundle\Model\Mapper;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Model\Mapper\Exception\MapperException;
use CleverConnectors\AppBundle\Model\Mapper\UniversalMapper;
use CleverConnectors\AppBundle\Model\MapTemplate\MapField;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\TestCase;
use Tests\PrivateTrait;

/**
 * Class UniversalMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Mapper
 */
final class UniversalMapperTest extends TestCase
{

    use PrivateTrait;

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::decodeData()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::encodeData()
     *
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::walkMapTemplate()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::getDataFromInputFields()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::reformatOutputData()
     *
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatText()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatUrl()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatDate()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatBool()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::formatNumber()
     *
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::isEmptyAndNotAllowed()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::setAllowedEmptyValues()
     */
    public function testProcessEmptyData(): void
    {
        $template = $this->getMap();
        $dto      = $this->getDto('{}');
        $mapper   = new UniversalMapper();

        $res = $mapper->process($template, $dto);
        self::assertEquals('[]', $res->getData());

        $res  = $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
        $data = json_decode($res->getData(), TRUE);
        self::assertTrue(is_array($data));
        self::assertArrayHasKey(TypeEnum::TEXT, $data);
        self::assertArrayHasKey(TypeEnum::URL, $data);
        self::assertArrayHasKey(TypeEnum::DATE, $data);
        self::assertArrayHasKey(TypeEnum::BOOL, $data);
        self::assertArrayHasKey(TypeEnum::NUMBER, $data);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::decodeData()
     */
    public function testProcessParseError(): void
    {
        $template = $this->getMap();
        $dto      = $this->getDto('{');
        $mapper   = new UniversalMapper();

        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::PARSE_ERROR);
        $mapper->process($template, $dto);
    }

    /**
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::process()
     * @covers \CleverConnectors\AppBundle\Model\Mapper\UniversalMapper::reformatOutputData()
     */
    public function testProcessFieldTypeError(): void
    {
        $badField = new MapField(TypeEnum::TEXT, new TypeEnum(TypeEnum::TEXT));

        $this->setProperty($badField, 'type', 'abc');

        $template = $this->getMap();
        $template->addField($badField);
        $dto    = $this->getDto('{}');
        $mapper = new UniversalMapper();

        $this->expectException(MapperException::class);
        $this->expectExceptionCode(MapperException::BAD_FIELD_TYPE);
        $mapper
            ->setAllowedEmptyValues(TRUE)
            ->process($template, $dto);
    }


    /**
     * ----------------------------------------- HELPERS -------------------------------
     */

    /**
     * @return MapTemplate;
     */
    private function getMap(): MapTemplate
    {
        $actionDto = new ActionDto('action', 'in');
        $map       = new MapTemplate();
        $map
            ->setAction($actionDto)
            ->setDirection($actionDto)
            ->addField(new MapField(TypeEnum::TEXT, new TypeEnum(TypeEnum::TEXT)))
            ->addField(new MapField(TypeEnum::URL, new TypeEnum(TypeEnum::URL)))
            ->addField(new MapField(TypeEnum::DATE, new TypeEnum(TypeEnum::DATE)))
            ->addField(new MapField(TypeEnum::BOOL, new TypeEnum(TypeEnum::BOOL)))
            ->addField(new MapField(TypeEnum::NUMBER, new TypeEnum(TypeEnum::NUMBER)));

        return $map;
    }

    /**
     * @param string $data
     *
     * @return ProcessDto
     */
    private function getDto(string $data): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders([])
            ->setData($data);

        return $dto;
    }

}