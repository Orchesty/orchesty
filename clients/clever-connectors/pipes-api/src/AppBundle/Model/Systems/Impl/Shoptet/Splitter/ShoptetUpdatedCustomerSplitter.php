<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Splitter;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class ShoptetUpdatedCustomerSplitter
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Splitter
 */
class ShoptetUpdatedCustomerSplitter implements CustomNodeInterface, BatchInterface
{

    /**
     * @var ProgressCounterService
     */
    private $progressCounterService;

    /**
     * ShoptetSyncCustomerConnector constructor.
     *
     * @param ProgressCounterService $progressCounterService
     */
    public function __construct(ProgressCounterService $progressCounterService)
    {
        $this->progressCounterService = $progressCounterService;
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     * @throws CleverConnectorsException
     * @throws SystemException
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists('CUSTOMERS', $data) || !is_array($data['CUSTOMERS'])) {
            throw new CleverConnectorsException(
                'Missing required CUSTOMER field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $i         = 0;
        $customers = $data['CUSTOMERS']['CUSTOMER'];

        if (is_array($customers)) {
            // when there is exactly 1 customer
            if (array_key_exists('GUID', $customers)) {
                $this->processCustomer($customers, $callbackItem, $i);
            } else {
                foreach ($customers as $key => $customer) {
                    $this->processCustomer($customer, $callbackItem, $i);
                    unset($customers[$key]);
                }
            }
        }

        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';
        $this->progressCounterService->setTotal($processId, $i);

        return resolve();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('ShoptetUpdatedCustomerSplitter has not implemented "process" function.');
    }

    /**
     * @param array $customer
     * @param int   $page
     *
     * @return SuccessMessage
     * @throws SystemException
     */
    protected function createSuccessMessage(array $customer, int $page): SuccessMessage
    {
        if (is_array($customer)) {
            $successMessage = new SuccessMessage($page);
            $successMessage->setData(json_encode($customer));
            unset($customer);

            return $successMessage;
        }

        throw new SystemException('Missing response data from Shoptet.', SystemException::MISSING_DATA);
    }

    /**
     * @param array    $customer
     * @param callable $callbackItem
     * @param int      $i
     *
     * @throws SystemException
     */
    private function processCustomer(array $customer, callable $callbackItem, int &$i): void
    {
        if (array_key_exists('ACCOUNTS', $customer) && is_array($customer['ACCOUNTS'])) {

            $accounts = $customer['ACCOUNTS']['ACCOUNT'];
            unset($customer['ACCOUNTS']);
            $newCustomer = $customer;

            if (is_array($accounts)) {
                // when there is exactly 1 account
                if (array_key_exists('GUID', $accounts)) {
                    $this->processAccount($accounts, $newCustomer, $callbackItem, $i);
                } else {
                    foreach ($accounts as $key2 => $account) {
                        $this->processAccount($account, $newCustomer, $callbackItem, $i);
                        unset($accounts[$key2]);
                    }
                }
            }
        }
    }

    /**
     * @param array    $account
     * @param array    $newCustomer
     * @param callable $callbackItem
     * @param int      $i
     *
     * @throws SystemException
     */
    public function processAccount(array $account, array $newCustomer, callable $callbackItem, int &$i): void
    {
        if (array_key_exists('EMAIL', $account)) {
            $newCustomer['ACCOUNT'] = $account;
            $callbackItem($this->createSuccessMessage($newCustomer, $i));
            $i++;
        }
    }

}