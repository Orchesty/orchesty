<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 9.4.18
 * Time: 16:31
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use DateTime;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Throwable;

/**
 * Class SalesforceAppCampaignsMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper
 */
class SalesforceAppCampaignsMapper implements CustomNodeInterface
{

    public const TITLE    = 'title';
    public const CAMPAIGN = 'campaign_id';
    public const CREATE   = 'create_time';

    public const STATISTICS = 'statistics';
    public const NAME       = 'name';
    public const ID         = 'external_id';
    public const STATUS     = 'status';
    public const SOURCE     = 'source';
    public const CREATED    = 'created_time';
    public const FROM       = 'send_from';
    public const TO         = 'send_to';
    public const URL        = 'external_url';
    public const C_RATE     = 'click_rate';
    public const CLICKS     = 'clicks';
    public const CLICKS_U   = 'clicks_unique';
    public const DOMAIN     = 'domain';
    public const O_RATE     = 'open_rate';
    public const OPENS      = 'opens';
    public const OPENS_U    = 'opens_unique';
    public const SENT       = 'sent';
    public const SPAM       = 'spam';
    public const SUB        = 'subscribers';
    public const U_SUB      = 'unsubscribed';
    public const U_DEL      = 'undelivered';

    /**
     * @var array
     */
    private $status = [
        0  => 'New',
        1  => 'Open (draft)',
        2  => 'Scheduled',
        3  => 'Sending (in progress)',
        4  => 'Sending',
        6  => 'Sent',
        8  => 'Batch & AI sending',
        11 => 'Open (stopped before sending)',
        12 => 'Open (stopped during sending)',
        13 => 'Reopened',
        19 => 'Stopping',
        50 => 'Error',
        81 => 'Obsolette editor',
        90 => 'Finished',
        99 => 'Delete',
    ];

    /**
     * @var array
     */
    private $source = [
        1 => 'CM Web',
        2 => 'Web services',
    ];

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (empty($data)) {
            throw new CleverConnectorsException('Data can not be empty', CleverConnectorsException::MISSING_DATA);
        }

        $output['results'] = $this->processData($data);

        return $dto->setData(json_encode($output));
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function processData(array $data): array
    {
        $ret = [];

        foreach ($data as $item) {
            $out = [];
            $this->addRequiredField($out, $item, self::TITLE, self::NAME);
            $this->addRequiredField($out, $item, self::CAMPAIGN, self::ID);
            $this->addRequiredField($out, $item, self::STATUS, NULL, $this->convertEnum(1));
            $this->addRequiredField($out, $item, self::SOURCE, NULL, $this->convertEnum(2));
            $this->addRequiredField($out, $item, self::CREATE, self::CREATED, $this->convertDate());

            $this->addOptionalField($out, $item, self::FROM, NULL, $this->convertDate());
            $this->addOptionalField($out, $item, self::TO, NULL, $this->convertDate());

            if (isset($item[self::STATISTICS]) && !empty($item[self::STATISTICS])) {
                $stat = $item[self::STATISTICS];

                $this->addOptionalField($out, $stat, self::URL);
                $this->addOptionalField($out, $stat, self::C_RATE);
                $this->addOptionalField($out, $stat, self::O_RATE);
                $this->addOptionalField($out, $stat, self::CLICKS);
                $this->addOptionalField($out, $stat, self::CLICKS_U);
                $this->addOptionalField($out, $stat, self::DOMAIN);
                $this->addOptionalField($out, $stat, self::OPENS);
                $this->addOptionalField($out, $stat, self::OPENS_U);
                $this->addOptionalField($out, $stat, self::SENT);
                $this->addOptionalField($out, $stat, self::SPAM);
                $this->addOptionalField($out, $stat, self::SUB);
                $this->addOptionalField($out, $stat, self::U_DEL);
                $this->addOptionalField($out, $stat, self::U_SUB);
            }
            $ret[] = $out;
            unset($item);
        }

        return $ret;
    }

    /**
     * @param array         $output
     * @param array         $input
     * @param string        $inputKey
     * @param string|null   $outputKey
     * @param callable|null $callback
     */
    private function addRequiredField(
        array &$output,
        array $input,
        string $inputKey,
        ?string $outputKey = NULL,
        ?callable $callback = NULL
    ): void
    {
        $outputKey = $outputKey ?? $inputKey;
        $value     = '';

        if (array_key_exists($inputKey, $input) && $input[$inputKey] !== NULL) {
            $value = $input[$inputKey];
        }

        $output[$outputKey] = $callback ? $callback($value) : $value;;
    }

    /**
     * @param array         $output
     * @param array         $input
     * @param string        $inputKey
     * @param string|null   $outputKey
     * @param callable|null $callback
     */
    private function addOptionalField(
        array &$output,
        array $input,
        string $inputKey,
        ?string $outputKey = NULL,
        ?callable $callback = NULL
    ): void
    {
        $outputKey = $outputKey ?? $inputKey;

        if (array_key_exists($inputKey, $input) && $input[$inputKey] !== NULL) {
            $value              = $input[$inputKey];
            $output[$outputKey] = $callback ? $callback($value) : $value;
        }
    }

    /**
     * @return callable
     */
    private function convertDate(): callable
    {
        return function (string $date) {
            try {
                $out = new DateTime($date);
            } catch (Throwable $e) {
                $out = new DateTime();
            }

            return $out->format('Y-m-d H:i:s.v\Z');
        };
    }

    /**
     * @param int $type
     *
     * @return callable
     */
    private function convertEnum(int $type): callable
    {
        if ($type === 1) {
            $enum = $this->status;
        } else {
            $enum = $this->source;
        }

        return function (int $val) use ($enum) {
            if (isset($enum[$val])) {
                return $enum[$val];
            }

            return 'undefined';
        };
    }

}