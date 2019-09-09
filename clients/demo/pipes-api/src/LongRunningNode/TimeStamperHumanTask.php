<?php declare(strict_types=1);

namespace Demo\LongRunningNode;

use Bunny\Message;
use DateTime;
use DateTimeZone;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeAbstract;

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
     * @throws Exception
     */
    public function beforeAction(Message $message): LongRunningNodeData
    {
        $data = LongRunningNodeData::fromMessage($message);

        return $data->setAuditLogs($this->createTimeStamp(json_decode($data->getData(), TRUE)));
    }

    /**
     * @param LongRunningNodeData $data
     * @param array               $requestData
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function afterAction(LongRunningNodeData $data, array $requestData): ProcessDto
    {
        $innerData = $this->createTimeStamp($data->getAuditLogs(), TRUE);
        $data->setData((string) json_encode(array_merge($requestData, $innerData)))->setAuditLogs($innerData);
        $this->dm->flush();

        return $data->toProcessDto();
    }

    /**
     * @param array $data
     * @param bool  $isAfter
     *
     * @return array
     * @throws Exception
     */
    private function createTimeStamp(array $data, bool $isAfter = FALSE): array
    {
        $dateTime = new DateTime('NOW', new DateTimeZone('UTC'));
        $key      = sprintf('%s%s', $isAfter ? '[A] ' : '[B] ', $dateTime->format('U'));

        $data['timestamp'][$key] = $dateTime->format('d. m. Y H:i:s');

        return $data;
    }

}