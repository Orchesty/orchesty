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
    private const TO      = ['jirsa.r@hanaboso.com', 'bucek.karel@gmail.com'];
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
        $dates = json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR);

        $dataDto = $this->dutyConnector->processAction($dto);
        $data    = json_decode($dataDto->getData(), FALSE, 512, JSON_THROW_ON_ERROR);
        $data    = json_encode($data, JSON_UNESCAPED_UNICODE);
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
