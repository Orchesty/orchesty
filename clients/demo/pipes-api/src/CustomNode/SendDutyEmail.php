<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Demo\Connector\PagerDutyConnector;
use EmailServiceBundle\Exception\MailerException;
use EmailServiceBundle\Mailer\Mailer;
use EmailServiceBundle\MessageBuilder\Impl\GenericMessageBuilder\GenericTransportMessage;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class SendDutyEmail
 *
 * @package Demo\CustomNode
 */
final class SendDutyEmail extends CommonNodeAbstract
{

    public const string NAME = 'send-duty-email';

    private const string FROM    = 'dev.email.hb@gmail.com';
    private const array TO       = [
        'pavlicek.m@hanaboso.com',
        'husak.j@hanaboso.com',
        'jirsa.r@hanaboso.com',
        'krecl.v@hanaboso.com',
        'info@hanaboso.com',
    ];
    private const string SUBJECT = 'Monitoring';

    /**
     * SendDutyEmail constructor.
     *
     * @param ApplicationInstallRepository $repository
     * @param Mailer                       $mailer
     * @param PagerDutyConnector           $dutyConnector
     */
    public function __construct(
        ApplicationInstallRepository $repository,
        private Mailer $mailer,
        private PagerDutyConnector $dutyConnector,
    )
    {
        parent::__construct($repository);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     * @throws CurlException
     * @throws DateTimeException
     * @throws JsonException
     * @throws MailerException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $dates = Json::decode($dto->getData());

        $dataDto = $this->dutyConnector->processAction($dto);
        $data    = Json::decode($dataDto->getData());
        $data    = Json::encode($data);
        $data    = str_replace(['}', '{'], ['', ''], $data);
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
            new GenericTransportMessage(self::FROM, $to, $subject, $data),
        );
    }

    /**
     * @param mixed[] $date
     *
     * @return string
     * @throws DateTimeException
     */
    private function getSubject(array $date): string
    {
        $since =
            $date['since'] ?? DateTimeUtils::getUtcDateTime('first day of last month')->format(DateTimeUtils::DATE);
        $till  =
            $date['until'] ?? DateTimeUtils::getUtcDateTime('first day of this month')->modify('+ 1 day')
            ->format(DateTimeUtils::DATE);

        return sprintf('%s %s — %s', self::SUBJECT, $since, $till);
    }

}
