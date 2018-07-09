<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Splitter;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShoptetUpdatedCustomerSplitterTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Splitter
 */
final class ShoptetUpdatedCustomerSplitterTest extends ConnectorTestCaseAbstract
{

    /**
     * @var int
     */
    private $i;

    /**
     * @covers ShoptetUpdatedCustomerSplitter::processBatch()
     */
    public function testProcessBatch2Accounts(): void
    {
        $splitter = $this->ownContainer->get('hbpf.custom_node.shoptet-updated-customer-splitter');
        $loop     = Factory::create();

        $this->i  = 0;
        $callback = function (SuccessMessage $successMessage): void {
            $result = Json::decode($successMessage->getData(), TRUE);

            $this->assertArrayHasKey('BILLING_ADDRESS', $result);
            $this->assertArrayHasKey('FULL_NAME', $result['BILLING_ADDRESS']);
            $this->assertArrayHasKey('#', $result['BILLING_ADDRESS']['FULL_NAME']);
            $this->assertArrayHasKey('ACCOUNT', $result);
            $this->assertArrayHasKey('GUID', $result['ACCOUNT']);
            $this->assertArrayHasKey('#', $result['ACCOUNT']['GUID']);
            $this->assertArrayHasKey('EMAIL', $result['ACCOUNT']);
            $this->assertArrayHasKey('#', $result['ACCOUNT']['EMAIL']);

            $this->i++;
        };

        $splitter->processBatch(
            (new ProcessDto())
                ->setData($this->getRequest('ShoptetCustomerForSplitter.json'))
                ->setHeaders([]),
            $loop,
            $callback
        );

        $loop->run();

        $this->assertEquals(2, $this->i);
    }

    /**
     * @covers ShoptetUpdatedCustomerSplitter::processBatch()
     */
    public function testProcessBatch1Account(): void
    {
        $splitter = $this->ownContainer->get('hbpf.custom_node.shoptet-updated-customer-splitter');
        $loop     = Factory::create();

        $this->i  = 0;
        $callback = function (SuccessMessage $successMessage): void {
            $result = Json::decode($successMessage->getData(), TRUE);

            $this->assertArrayHasKey('BILLING_ADDRESS', $result);
            $this->assertArrayHasKey('FULL_NAME', $result['BILLING_ADDRESS']);
            $this->assertArrayHasKey('#', $result['BILLING_ADDRESS']['FULL_NAME']);
            $this->assertArrayHasKey('ACCOUNT', $result);
            $this->assertArrayHasKey('GUID', $result['ACCOUNT']);
            $this->assertArrayHasKey('#', $result['ACCOUNT']['GUID']);
            $this->assertArrayHasKey('EMAIL', $result['ACCOUNT']);
            $this->assertArrayHasKey('#', $result['ACCOUNT']['EMAIL']);

            $this->i++;
        };

        $splitter->processBatch(
            (new ProcessDto())
                ->setData($this->getRequest('ShoptetCustomerForSplitter1Account.json'))
                ->setHeaders([]),
            $loop,
            $callback
        );

        $loop->run();

        $this->assertEquals(1, $this->i);
    }

    /**
     * @covers ShoptetUpdatedCustomerSplitter::processBatch()
     */
    public function testProcessBatchMoreCustomers(): void
    {
        $splitter = $this->ownContainer->get('hbpf.custom_node.shoptet-updated-customer-splitter');
        $loop     = Factory::create();

        $this->i  = 0;
        $callback = function (SuccessMessage $successMessage): void {
            $result = Json::decode($successMessage->getData(), TRUE);

            $this->assertArrayHasKey('BILLING_ADDRESS', $result);
            $this->assertArrayHasKey('FULL_NAME', $result['BILLING_ADDRESS']);
            $this->assertArrayHasKey('#', $result['BILLING_ADDRESS']['FULL_NAME']);
            $this->assertArrayHasKey('ACCOUNT', $result);
            $this->assertArrayHasKey('GUID', $result['ACCOUNT']);
            $this->assertArrayHasKey('#', $result['ACCOUNT']['GUID']);
            $this->assertArrayHasKey('EMAIL', $result['ACCOUNT']);
            $this->assertArrayHasKey('#', $result['ACCOUNT']['EMAIL']);

            $this->i++;
        };

        $splitter->processBatch(
            (new ProcessDto())
                ->setData($this->getRequest('ShoptetCustomersForSplitter.json'))
                ->setHeaders([]),
            $loop,
            $callback
        );

        $loop->run();

        $this->assertEquals(7, $this->i);
    }

}