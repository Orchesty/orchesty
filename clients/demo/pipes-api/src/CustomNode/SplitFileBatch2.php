<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\PipesPhpSdk\CustomNode\Impl\RabbitCustomNode;

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
     */
    protected function processBatch(ProcessDto $dto): void
    {
        $data = json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR);

        if (array_key_exists('data', $data)) {
            $data = json_decode($data['data'], TRUE, 512, JSON_THROW_ON_ERROR);

            $datetime = DateTimeUtils::getUTCDateTime();
            if ($datetime->getTimestamp() % 2 == 0) {
                unset($data['bids']);
            } else {
                unset($data['asks']);
            }

            $this->publishMessage($data, $dto->getHeaders());
        }
    }

}
