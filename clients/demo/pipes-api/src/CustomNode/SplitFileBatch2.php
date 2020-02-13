<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;

/**
 * Class SplitFileBatch2
 *
 * @package Demo\CustomNode
 */
class SplitFileBatch2 extends RabbitCustomNode
{

    /**
     * @param ProcessDto $dto
     *
     * @throws DateTimeException
     * @throws Exception
     */
    protected function processBatch(ProcessDto $dto): void
    {
        $data = Json::decode($dto->getData());

        if (array_key_exists('data', $data)) {
            $datetime = DateTimeUtils::getUtcDateTime();
            if ($datetime->getTimestamp() % 2 == 0) {
                unset($data['bids']);
            } else {
                unset($data['asks']);
            }

            $this->publishMessage($data, $dto->getHeaders());
        }
    }

}
