<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Demo\Connector\PagerDutyConnector;
use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Mailer\Mailer;
use EmailServiceBundle\MessageBuilder\Impl\GenericMessageBuilder\GenericTransportMessage;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class SendDutyEmail
 *
 * @package Demo\CustomNode
 */
class SendDutyEmail extends CustomNodeAbstract
{

    private const FROM    = 'dev.email.hb@gmail.com';
    private const TO      = [
        'pavlicek.m@hanaboso.com',
        'husak.j@hanaboso.com',
        'jirsa.r@hanaboso.com',
        'prochazka.t@hanaboso.com',
        'krecl.v@hanaboso.com',
        'info@hanaboso.com',
    ];
    private const SUBJECT = 'Monitoring';

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var PagerDutyConnector
     */
    private $dutyConnector;

    /**
     * SendDutyEmail constructor.
     *
     * @param Mailer             $mailer
     * @param PagerDutyConnector $dutyConnector
     */
    public function __construct(Mailer $mailer, PagerDutyConnector $dutyConnector)
    {
        $this->mailer        = $mailer;
        $this->dutyConnector = $dutyConnector;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CurlException
     * @throws DateTimeException
     * @throws MailerException
     * @throws ConnectorException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $dates = Json::decode($dto->getData());

        $dataDto = $this->dutyConnector->processAction($dto);
        $data    = Json::decode($dataDto->getData());
        $data    = Json::encode($data);
        $data    = str_replace(['}', '{'], ['', ''], (string) $data);
        $data    = str_replace('"', '', $data);
        $data    = str_replace(':hours:', ': ', $data);
        $data    = str_replace(',', sprintf(',%s', PHP_EOL), $data);

        $subject = $this->getSubject($dates);
        foreach (self::TO as $to) {
            $this->send($data, $subject, $to);
        }

        return $dataDto->setData($data);
    }

    /**
     * @param string $data
     * @param string $subject
     * @param string $to
     *
     * @throws MailerException
     */
    private function send(string $data, string $subject, string $to): void
    {
        $this->mailer->renderAndSend(
            new GenericTransportMessage(self::FROM, $to, $subject, $data)
        );
    }

    /**
     * @param array $date
     *
     * @return string
     * @throws DateTimeException
     */
    private function getSubject(array $date): string
    {
        $since =
            $date['since'] ?? DateTimeUtils::getUtcDateTime('first day of last month')->format(DateTimeUtils::DATE);
        $till  =
            $date['until'] ?? DateTimeUtils::getUtcDateTime('first day of this month')
                ->modify('+ 1 day')
                ->format(DateTimeUtils::DATE);

        return sprintf('%s %s — %s', self::SUBJECT, $since, $till);
    }

}
