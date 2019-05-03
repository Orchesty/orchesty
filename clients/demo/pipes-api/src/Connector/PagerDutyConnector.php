<?php declare(strict_types=1);

namespace Demo\Connector;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use JK\Utils\CzechHolidays;

/**
 * Class PagerDutyConnector
 *
 * @package Demo\Connector
 */
class PagerDutyConnector implements ConnectorInterface
{

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * PagerDutyConnector constructor.
     *
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(CurlManagerInterface $curlManager)
    {
        $this->curlManager = $curlManager;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'pager_duty.schedule';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $dto;
        throw new ConnectorException(
            sprintf('Process not event is not implemented for PagerDutyConnector')
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $requestDto = new RequestDto('GET', new Uri(
                sprintf(
                    'https://api.pagerduty.com/schedules/PUUDPGA?time_zone=UTC&since=%s&until=%s',
                    json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR)['since'] ?? date('Y-m-01'),
                    json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR)['until'] ?? date('Y-m-d'),
                    )
            )
        );
        $requestDto->setHeaders([
            'Accept'        => 'application/vnd.pagerduty+json;version=2',
            'Authorization' => 'Token token=pu51uTEKrZcUrS5a9ev4',
        ]);

        $response = $this->curlManager->send($requestDto);
        if ($response->getStatusCode() !== 200) {
            throw new Exception(sprintf('Server response with status code [%s]', $response->getStatusCode()));
        }
        $json          = json_decode($response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
        $finalSchedule = $json['schedule']['final_schedule']['rendered_schedule_entries'] ?? '';

        $res = [];
        /** @var array $day */
        foreach ($finalSchedule as $day) {
            $user  = $day['user']['summary'] ?? '';
            $hours = $this->getHours($day['start'] ?? '', $day['end'] ?? '');

            if (!$this->isWeekendOrHoliday($day['start'])) {
                $hours -= 8;
                if ($hours < 0) {
                    $hours = 0;
                }
            }

            if (array_key_exists($user, $res)) {
                $res[$user]['hours'] += $hours;
            } else {
                $res[$user] = ['hours' => $hours];
            }
        }

        return $dto->setData((string) json_encode($res));
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    private function isWeekendOrHoliday(string $date): bool
    {
        $date = date('Y-m-d', (int) strtotime($date));
        /** @var DateTimeInterface $day */
        $day = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return date('N', (int) strtotime($date)) >= 6 || CzechHolidays::isHoliday($day);
    }

    /**
     * @param string $startDay
     * @param string $endDay
     *
     * @return int
     * @throws Exception
     */
    private function getHours(string $startDay, string $endDay): int
    {
        $start = new DateTime($startDay);
        $end   = new DateTime($endDay);
        $diff  = $end->diff($start);
        $hours = $diff->h;

        return (int) ($hours + ($diff->days * 24));
    }

}