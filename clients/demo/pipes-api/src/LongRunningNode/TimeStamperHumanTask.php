<?php declare(strict_types=1);

namespace Demo\LongRunningNode;

use Bunny\Message;
use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesFramework\LongRunningNode\Model\Impl\LongRunningNodeAbstract;

/**
 * Class TimeStamperHumanTask
 *
 * @package Demo\LongRunningNode
 */
final class TimeStamperHumanTask extends LongRunningNodeAbstract
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * TimeStamperHumanTask constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hbpf.long_running.time-stamper';
    }

    /**
     * @param Message $message
     *
     * @return LongRunningNodeData
     */
    public function beforeAction(Message $message): LongRunningNodeData
    {
        $data = LongRunningNodeData::fromMessage($message);

        return $data->setAuditLogs($this->createTimeStamp(json_decode($data->getData(), TRUE)));
    }

    /**
     * @param LongRunningNodeData $data
     * @param string              $requestData
     *
     * @return ProcessDto
     */
    public function afterAction(LongRunningNodeData $data, string $requestData): ProcessDto
    {
        $requestData;

        $innerData = $this->createTimeStamp($data->getAuditLogs(), TRUE);
        $data->setData(json_encode($innerData))->setAuditLogs($innerData);
        $this->dm->flush();

        return $data->toProcessDto();
    }

    /**
     * @param array $data
     * @param bool  $isAfter
     *
     * @return array
     */
    private function createTimeStamp(array $data, bool $isAfter = FALSE): array
    {
        $dateTime = new DateTime('NOW', new DateTimeZone('UTC'));
        $key      = sprintf('%s%s', $isAfter ? '[A] ' : '[B] ', $dateTime->format('U'));

        $data['timestamp'][$key] = $dateTime->format('d. m. Y H:i:s');

        return $data;
    }

}