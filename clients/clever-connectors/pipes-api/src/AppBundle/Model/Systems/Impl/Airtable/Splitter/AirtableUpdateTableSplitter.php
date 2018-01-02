<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Splitter;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Class AirtableUpdateTableSplitter
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Splitter
 */
class AirtableUpdateTableSplitter extends AirtableTableSplitter
{

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

        $data  = json_decode($dto->getData(), TRUE);
        $lists = $data[CleverFieldsEnum::LISTS];

        $i = 0;
        if (array_key_exists(SystemInstall::FORMS, $sett)) {
            foreach ($sett[SystemInstall::FORMS] as $form) {
                if (in_array($form[AirtableSystem::LIST_ID], $lists)) {
                    ++$i;
                    $callbackItem($this->createSuccessMessage($form, $i, $dto));
                }
            }
        }

        $processId = CMHeaders::get(CMHeaders::PROCESS_ID, $dto->getHeaders()) ?? '';
        $this->progressCounterService->setTotal($processId, $i);

        return resolve();
    }

}