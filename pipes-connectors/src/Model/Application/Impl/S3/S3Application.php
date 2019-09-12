<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\S3;

use Aws\S3\S3Client;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use LogicException;

/**
 * Class S3Application
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\S3
 */
final class S3Application extends BasicApplicationAbstract
{

    public const KEY      = 'key';
    public const SECRET   = 'secret';
    public const REGION   = 'region';
    public const VERSION  = 'version';
    public const BUCKET   = 'bucket';
    public const ENDPOINT = 'endpoint';

    private const CREDENTIALS = 'credentials';

    private const REGIONS = [
        'us-east-2'      => 'US East (Ohio) - us-east-2',
        'us-east-1'      => 'US East (N. Virginia) - us-east-1',
        'us-west-1'      => 'US West (N. California) - us-west-1',
        'us-west-2'      => 'US West (Oregon) - us-west-2',
        'ap-east-1'      => 'Asia Pacific (Hong Kong) - ap-east-1',
        'ap-south-1'     => 'Asia Pacific (Mumbai) - ap-south-1',
        'ap-northeast-2' => 'Asia Pacific (Seoul) - ap-northeast-2',
        'ap-southeast-1' => 'Asia Pacific (Singapore) - ap-southeast-1',
        'ap-southeast-2' => 'Asia Pacific (Sydney) - ap-southeast-2',
        'ap-northeast-1' => 'Asia Pacific (Tokyo) - ap-northeast-1',
        'ca-central-1'   => 'Canada (Central) - ca-central-1',
        'cn-north-1'     => 'China (Beijing) - cn-north-1',
        'cn-northwest-1' => 'China (Ningxia) - cn-northwest-1',
        'eu-central-1'   => 'EU (Frankfurt) - eu-central-1',
        'eu-west-1'      => 'EU (Ireland) - eu-west-1',
        'eu-west-2'      => 'EU (London) - eu-west-2',
        'eu-west-3'      => 'EU (Paris) - eu-west-3',
        'eu-north-1'     => 'EU (Stockholm) - eu-north-1',
        'me-south-1'     => 'Middle East (Bahrain) - me-south-1',
        'sa-east-1'      => 'South America (Sao Paulo) - sa-east-1',
        'us-gov-east-1'  => 'AWS GovCloud (US-East) - us-gov-east-1',
        'us-gov-west-1'  => 'AWS GovCloud (US-West) - us-gov-west-1',
    ];

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 's3';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Amazon Simple Storage Service';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Amazon Simple Storage Service (Amazon S3) is an object storage service that offers industry-leading scalability, data availability, security, and performance.';
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        if (!isset($applicationInstall->getSettings()[ApplicationAbstract::FORM])) {
            return FALSE;
        }

        $settings = $applicationInstall->getSettings()[ApplicationAbstract::FORM];

        return isset($settings[self::KEY])
            && isset($settings[self::SECRET])
            && isset($settings[self::BUCKET])
            && isset($settings[self::REGION]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL
    ): RequestDto {
        $applicationInstall;
        $method;
        $url;
        $data;

        throw new LogicException(sprintf(
            "Method '%s' is not supported! Use '%s' instead!",
            __METHOD__,
            str_replace('getRequestDto', 'getS3Client', __METHOD__)
        ));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return S3Client
     */
    public function getS3Client(ApplicationInstall $applicationInstall): S3Client
    {
        $settings = $applicationInstall->getSettings()[BasicApplicationAbstract::FORM];
        $endpoint = $settings[self::ENDPOINT];

        return new S3Client(array_merge([
            self::CREDENTIALS => [
                self::KEY    => $settings[self::KEY],
                self::SECRET => $settings[self::SECRET],
            ],
            self::REGION      => $settings[self::REGION],
            self::VERSION     => 'latest',
        ], $endpoint ? [self::ENDPOINT => $settings[self::ENDPOINT]] : []));
    }

    /**
     * @return Form
     * @throws ApplicationInstallException
     */
    public function getSettingsForm(): Form
    {
        return (new Form())
            ->addField((new Field(Field::TEXT, self::KEY, 'Key', NULL, TRUE)))
            ->addField((new Field(Field::TEXT, self::SECRET, 'Secret', NULL, TRUE)))
            ->addField((new Field(Field::TEXT, self::BUCKET, 'Bucket', NULL, TRUE)))
            ->addField((new Field(Field::SELECT_BOX, self::REGION, 'Region', NULL, TRUE))->setChoices(self::REGIONS))
            ->addField((new Field(Field::TEXT, self::ENDPOINT, 'Custom Endpoint')));
    }

}
