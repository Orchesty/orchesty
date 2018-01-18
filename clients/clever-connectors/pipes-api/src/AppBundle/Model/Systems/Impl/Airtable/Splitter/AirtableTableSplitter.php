<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Splitter;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use LogicException;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class AirtableTableSplitter
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Splitter
 */
class AirtableTableSplitter implements BatchInterface, CustomNodeInterface
{

    /**
     * @var ProgressCounterService
     */
    protected $progressCounterService;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * AirtableTableSplitter constructor.
     *
     * @param ProgressCounterService $progressCounterService
     * @param DocumentManager        $dm
     */
    public function __construct(ProgressCounterService $progressCounterService, DocumentManager $dm)
    {
        $this->progressCounterService  = $progressCounterService;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto    $dto
     * @param LoopInterface $loop
     * @param callable      $callbackItem
     *
     * @return PromiseInterface
     */
    public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
    {
        $sys  = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $sett = $sys->getSettings();

        $i = 0;
        if (array_key_exists(SystemInstall::FORMS, $sett)) {
            foreach ($sett[SystemInstall::FORMS] as $form) {
                ++$i;
                $callbackItem($this->createSuccessMessage($form, $i, $dto));
            }
        }

        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';
        $this->progressCounterService->setTotal($processId, $i);

        return resolve();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws SystemException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('AirtableTableSplitter has not implemented "process" function.');
    }

    /**
     * @param array      $form
     * @param int        $i
     * @param ProcessDto $dto
     *
     * @return SuccessMessage
     */
    protected function createSuccessMessage(array $form, int &$i, ProcessDto $dto): SuccessMessage
    {
        if (array_key_exists(AirtableSystem::TABLE_URL, $form)) {
            $successMessage = new SuccessMessage($i);
            $successMessage
                ->addHeader(CMHeaders::createKey(AirtableSystem::TABLE_URL), $form[AirtableSystem::TABLE_URL])
                ->addHeader(CMHeaders::createKey(AirtableSystem::LIST_ID), $form[AirtableSystem::LIST_ID])
                ->setData($dto->getData());

            if ($form[AirtableSystem::VIEW]) {
                $successMessage->addHeader(CMHeaders::createKey(AirtableSystem::VIEW), $form[AirtableSystem::VIEW]);
            }

            return $successMessage;
        }

        throw new LogicException(
            'Missing table url in custom form data, Airtable splitter.'
        );
    }

}