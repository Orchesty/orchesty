<?php declare(strict_types=1);

namespace Demo\Connector;

use DateTimeImmutable;
use DateTimeInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use JK\Utils\CzechHolidays;

/**
 * Class PagerDutyConnector
 *
 * @package Demo\Connector
 */
class PagerDutyConnector extends ConnectorAbstract
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
     * @throws ConnectorException
     * @throws DateTimeException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $requestDto = new RequestDto(
            CurlManager::METHOD_GET,
            $this->getUrl($dto),
            array_merge(
                $dto->getHeaders(),
                [
                    'Accept'        => 'application/vnd.pagerduty+json;version=2',
                    'Authorization' => 'Token token=pu51uTEKrZcUrS5a9ev4',
                ]
            )
        );
        $requestDto->setDebugInfo($dto);

        $response = $this->curlManager->send($requestDto);
        if ($response->getStatusCode() !== 200) {
            throw new ConnectorException(sprintf('Server response with status code [%s]', $response->getStatusCode()));
        }
        $json          = Json::decode($response->getBody());
        $finalSchedule = $json['schedule']['final_schedule']['rendered_schedule_entries'] ?? [''];
        array_shift($finalSchedule);
        array_pop($finalSchedule);

        $minuses = [];
        $res     = [];
        /** @var array $day */
        foreach ($finalSchedule as $day) {
            $user       = $day['user']['summary'] ?? '';
            $hours      = $this->getHours($day['start'] ?? '', $day['end'] ?? '');
            $minusIndex = DateTimeUtils::getUtcDateTime($day['start'])->format(DateTimeUtils::DATE);
            $minus      = $minuses[$minusIndex] ?? 8;

            if ($hours > 24) {
                // Split days if is merged into one interval (override)
                $since     = DateTimeUtils::getUtcDateTime($day['start']);
                $till      = DateTimeUtils::getUtcDateTime($day['end']);
                $daysCount = $till->diff($since)->d;

                for ($i = 1; $i <= $daysCount; $i++) {
                    $this->getComputedHours($since->format(DATE_ATOM), $hours, $minus);
                    $since->modify('+ 1 Day');
                }
            } else {
                $this->getComputedHours($day['start'], $hours, $minus);
            }
            $minuses[$minusIndex] = $minus;

            if (array_key_exists($user, $res)) {
                $res[$user]['hours'] += $hours;
            } else {
                $res[$user] = ['hours' => $hours];
            }
        }

        return $dto->setData(Json::encode($res));
    }

    /**
     * @param string $day
     * @param int    $hours
     * @param int    $minus
     */
    private function getComputedHours(string $day, int &$hours, int &$minus = 8): void
    {
        if (!$this->isWeekendOrHoliday($day)) {
            if ($hours < $minus) {
                $minus = abs($hours - $minus);
                $hours = 0;

                return;
            } else {
                $hours -= $minus;
            }
        }
        $minus = 0;
    }

    /**
     * @param string $date
     *
     * @return bool
     */
    private function isWeekendOrHoliday(string $date): bool
    {
        /** @var DateTimeInterface $day */
        $day = DateTimeImmutable::createFromFormat('Y-m-d', substr($date, 0, 10));

        return date('N', (int) strtotime($date)) >= 6 || CzechHolidays::isHoliday($day);
    }

    /**
     * @param string $startDay
     * @param string $endDay
     *
     * @return int
     * @throws DateTimeException
     */
    private function getHours(string $startDay, string $endDay): int
    {
        $start = DateTimeUtils::getUtcDateTime($startDay);
        $end   = DateTimeUtils::getUtcDateTime($endDay);
        $diff  = $end->diff($start);
        $hours = $diff->h;

        return (int) ($hours + ($diff->days * 24));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return Uri
     * @throws DateTimeException
     */
    private function getUrl(ProcessDto $dto): Uri
    {
        $data  = Json::decode($dto->getData());
        $since = $data['since'] ??
            DateTimeUtils::getUtcDateTime('first day of last month')->format(DateTimeUtils::DATE);
        $till  = $data['until'] ??
            DateTimeUtils::getUtcDateTime('first day of this month')
                ->modify('+ 1 day')
                ->format(DateTimeUtils::DATE);

        return new Uri(
            sprintf(
                'https://api.pagerduty.com/schedules/PUUDPGA?time_zone=CET&since=%s&until=%s',
                $since,
                $till,
            )
        );
    }

}
