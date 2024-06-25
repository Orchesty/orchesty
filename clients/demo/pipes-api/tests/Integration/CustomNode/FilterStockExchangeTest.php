<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\FilterStockExchange;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class FilterStockExchangeTest
 *
 * @package DemoTests\Integration\CustomNode
 */
#[CoversClass(FilterStockExchange::class)]
final class FilterStockExchangeTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var FilterStockExchange $customNode */
        $customNode = self::getContainer()->get('hbpf.custom_node.filter-bids');

        $dto = $customNode->processAction((new ProcessDto())->setData('{"bids": {"foo":"bar"}}'));

        self::assertEquals('{"foo":"bar"}', $dto->getData());

        $this->getFunctionMock('Demo\CustomNode', 'mt_rand')
            ->expects(self::any())
            ->willReturn(5);

        $dto = $customNode->processAction((new ProcessDto())->setData('{}'));
        self::assertEquals('', $dto->getData());
    }

}
