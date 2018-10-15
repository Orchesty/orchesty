<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\Comparator;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\TestCase;

/**
 * Class ComparatorTest
 *
 * @package Tests\Unit\AppBundle\Model\CustomNode
 */
final class ComparatorTest extends TestCase
{

    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * Creates custom node
     */
    public function setUp(): void
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
                    'src'       => [1, 2, 3, 4, 5],
                    'dst'       => [4, 5, 6, 7],
                    'pass_data' => 'asd',
                ],
                [
                    'create'    => [1, 2, 3],
                    'delete'    => [6, 7],
                    'update'    => [],
                    'pass_data' => 'asd',
                ],
            ],
            // try bigger arrays
            [
                [
                    'src' => $this->generateSeries(0, 90000),
                    'dst' => $this->generateSeries(90001, 99999),
                ],
                [
                    'create' => $this->generateSeries(0, 90000),
                    'delete' => $this->generateSeries(90001, 99999),
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
                    'src'      => [['id' => 'a'], ['id' => 'b'], ['id' => 'c']],
                    'dst'      => [['id' => 'c'], ['id' => 'd']],
                    'settings' => ['id_key' => 'id'],
                ],
                [
                    'create' => [['id' => 'a'], ['id' => 'b']],
                    'delete' => [['id' => 'd']],
                    'update' => [],
                ],
            ],
            [
                [
                    'src'      => [['id' => 'a', 'foo' => 'bar'], ['id' => 'b'], ['id' => 'c']],
                    'dst'      => [['id' => 'c', 'loo' => 'doo'], ['id' => 'd', 'baz' => 'bat']],
                    'settings' => ['id_key' => 'id'],
                ],
                [
                    'create' => [['id' => 'a', 'foo' => 'bar'], ['id' => 'b']],
                    'delete' => [['id' => 'd', 'baz' => 'bat']],
                    'update' => [],
                ],
            ],
            [
                [
                    'src'      => [
                        ['id' => 'a', 'cid' => 'a', 'email' => 'a@a.a'],
                        ['id' => 'b', 'cid' => 'b', 'email' => 'b@b.b'],
                        ['id' => 'c', 'cid' => 'c', 'email' => 'c@c.c'],
                    ],
                    'dst'      => [
                        ['id' => 'b', 'cid' => 'b', 'email' => 'b@b.b'],
                        ['id' => 'c', 'cid' => 'not-c', 'email' => 'c@c.c'],
                        ['id' => 'd', 'cid' => 'd', 'email' => 'd@d.d'],
                    ],
                    'settings' => ['id_key' => 'id', 'compare_key' => 'cid'],
                ],
                [
                    'create' => [['id' => 'a', 'cid' => 'a', 'email' => 'a@a.a']],
                    'delete' => [['id' => 'd', 'cid' => 'd', 'email' => 'd@d.d']],
                    'update' => [['id' => 'c', 'cid' => 'c', 'email' => 'c@c.c']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param array $data
     * @param bool  $isValid
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
    private function generateSeries(int $start, int $stop, ?string $key = NULL): array
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
