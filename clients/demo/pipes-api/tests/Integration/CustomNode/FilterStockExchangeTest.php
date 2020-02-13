<?php declare(strict_types=1);

namespace DemoTests\Integration\CustomNode;

use Demo\CustomNode\FilterStockExchange;
use DemoTests\KernelTestCaseAbstract;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class FilterStockExchangeTest
 *
 * @package DemoTests\Integration\CustomNode
 */
final class FilterStockExchangeTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Demo\CustomNode\FilterStockExchange
     * @covers \Demo\CustomNode\FilterStockExchange::process
     * @throws Exception
     */
    public function testProcess(): void
    {
        /** @var FilterStockExchange $customNode */
        $customNode = self::$container->get('hbpf.custom_node.filter-bids');

        $dto = $customNode->process((new ProcessDto())->setData('{"bids": {"foo":"bar"}}'));

        self::assertEquals('{"foo":"bar"}', $dto->getData());

        $this->getFunctionMock('Demo\CustomNode', 'mt_rand')
            ->expects(self::any())
            ->willReturn(5);

        $dto = $customNode->process((new ProcessDto())->setData('{}'));
        self::assertEquals('', $dto->getData());
    }

}
