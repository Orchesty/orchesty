<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3;

use Aws\S3\S3Client;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\AwsApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;

/**
 * Class S3Application
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\S3
 */
final class S3Application extends AwsApplicationAbstract
{

    public const BUCKET = 'bucket';

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
     * @return Form
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
     *
     * @return S3Client
     */
    public function getS3Client(ApplicationInstall $applicationInstall): S3Client
    {
        $settings = $applicationInstall->getSettings()[self::FORM];
        $endpoint = $settings[self::ENDPOINT];

        return new S3Client(
            array_merge(
                [
                    self::CREDENTIALS => [
                        self::KEY    => $settings[self::KEY],
                        self::SECRET => $settings[self::SECRET],
                    ],
                    self::REGION      => $settings[self::REGION],
                    self::VERSION     => self::LATEST,
                ],
                $endpoint ? [self::ENDPOINT => $settings[self::ENDPOINT]] : [],
            ),
        );
    }

}
