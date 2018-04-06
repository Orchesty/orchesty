<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\TestCase;

final class ComparatorTest extends TestCase
{

    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * Creates custom node
     */
    public function setUp()
    {
        $this->comparator = new Comparator();
    }

    /**
     * @dataProvider compareDataProvider
     *
     * @param array $input
     * @param array $output
     *
     * @throws CleverConnectorsException
     */
    public function testProcessCompare(array $input, array $output): void
    {
        $dto = new ProcessDto();
        $dto->setData(json_encode($input));

        $result = $this->comparator->process($dto);

        $this->assertEquals($output, json_decode($result->getData(), TRUE));
    }

    /**
     * @return array
     */
    public function compareDataProvider(): array
    {
        return [
            [
                [
                    'src' => [1,2,3,4,5],
                    'dst' => [4,5,6,7],
                ],
                [
                    'create' => [1,2,3],
                    'delete' => [6,7],
                    'update' => [],
                ],
            ],
            [
                [
                    'src' => ['a', 'b', 'c'],
                    'dst' => ['c', 'd'],
                ],
                [
                    'create' => ['a', 'b'],
                    'delete' => ['d'],
                    'update' => [],
                ],
            ],
            [
                [
                    'src' => [['id' => 'a'], ['id' => 'b'], ['id' => 'c']],
                    'dst' => [['id' => 'c'], ['id' => 'd']],
                    'settings' => ['id_key' => 'id']
                ],
                [
                    'create' => ['a', 'b'],
                    'delete' => ['d'],
                    'update' => [],
                ],
            ],
            [
                [
                    'src' => [['id' => 'a', 'cid' => 'a'], ['id' => 'b', 'cid' => 'b'], ['id' => 'c', 'cid' => 'c']],
                    'dst' => [['id' => 'c', 'cid' => 'c'], ['id' => 'd', 'cid' => 'd']],
                    'settings' => ['id_key' => 'id', 'compare_key' => 'cid']
                ],
                [
                    'create' => ['a', 'b'],
                    'delete' => ['d'],
                    'update' => [],
                ],
            ],
//            [
//                [
//                    'src' => $this->generateSeries(1, 9999),
//                    'dst' => $this->generateSeries(9000, 9999),
//                ],
//                [
//                    'create' => $this->generateSeries(1, 9000),
//                    'delete' => $this->generateSeries(9000, 9999),
//                    'update' => [],
//                ],
//            ],
        ];
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param array $data
     * @param bool  $isValid
     *
     */
    public function testProcessValidateData(array $data, bool $isValid): void
    {
        try {
            $dto = new ProcessDto();
            $dto->setData(json_encode($data));
            $this->comparator->process($dto);
            $this->assertTrue($isValid);
        } catch (CleverConnectorsException $e) {
            $this->assertFalse($isValid);
        }
    }

    /**
     * @return array
     */
    public function validDataProvider(): array
    {
        return [
            [[], FALSE],
            [['src' => []], FALSE],
            [['dst' => []], FALSE],
            [['src' => [], 'dst' => []], TRUE],
            [['src' => [], 'dst' => [], 'settings' => []], TRUE],
            [['src' => [], 'dst' => [], 'settings' => ['id_key' => 'id']], TRUE],
            [['src' => [], 'dst' => [], 'settings' => ['compare_key' => 'id']], FALSE],
            [['src' => [], 'dst' => [], 'settings' => ['id_key' => 'id', 'compare_key' => 'id']], TRUE],
        ];
    }

    /**
     * @param int         $start
     * @param int         $stop
     * @param string|NULL $key
     *
     * @return array
     */
    private function generateSeries(int $start, int $stop, string $key = NULL): array
    {
        $s = [];

        for ($i = $start; $i <= $stop; $i++) {
            if ($key) {
                $s[] = [$key => $i];
            } else {
                $s[] = $i;
            }
        }

        return $s;
    }
    
}
