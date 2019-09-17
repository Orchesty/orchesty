<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift;

use Aws\Redshift\RedshiftClient;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\AwsApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;

/**
 * Class RedshiftApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift
 */
class RedshiftApplication extends AwsApplicationAbstract
{

    private const   HOST               = 'host';
    private const   PORT               = 'Port';
    private const   DBNAME             = 'DBName';
    private const   ADDRESS            = 'Address';
    private const   MASTER_USER        = 'MasterUsername';
    private const   CLUSTER_IDENTIFIER = 'ClusterIdentifier';

    public const    ENDPOINT    = 'Endpoint';
    public const    DB_PASSWORD = 'DbPassword';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'redshift';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Amazon Redshift';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Amazon Redshift is a fast, simple, cost-effective data warehousing service.';
    }

    /**
     * @return Form
     * @throws ApplicationInstallException
     */
    public function getSettingsForm(): Form
    {
        $form = new Form();
        $form
            ->addField(new Field(Field::TEXT, self::KEY, 'Key', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::SECRET, 'Secret', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::DB_PASSWORD, 'Database Password', NULL, TRUE))
            ->addField((new Field(Field::SELECT_BOX, self::REGION, 'Region', '', TRUE))->setChoices(self::REGIONS));

        return $form;
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
            && isset($settings[self::REGION])
            && isset($settings[self::DB_PASSWORD]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $settings
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        $applicationInstall = parent::setApplicationSettings($applicationInstall, $settings);

        $settings   = $applicationInstall->getSettings()[self::FORM];
        $key        = $settings[self::KEY];
        $secret     = $settings[self::SECRET];
        $region     = $settings[self::REGION];
        $dbPassword = $settings[self::DB_PASSWORD];

        $client = RedshiftClient::factory([
            self::CREDENTIALS => [
                self::KEY    => $key,
                self::SECRET => $secret,
            ],
            self::REGION      => $region,
            self::VERSION     => 'latest',
        ]);

        $cluster = $client->describeClusters()->get('Clusters')[0];

        if (!$cluster) {
            throw new ApplicationInstallException('Login into application was unsuccessful.');
        }

        return $applicationInstall->setSettings([
            RedshiftApplication::CLUSTER_IDENTIFIER => $cluster[RedshiftApplication::CLUSTER_IDENTIFIER],
            RedshiftApplication::MASTER_USER        => $cluster[RedshiftApplication::MASTER_USER],
            RedshiftApplication::DB_PASSWORD        => $dbPassword,
            RedshiftApplication::DBNAME             => $cluster[RedshiftApplication::DBNAME],
            RedshiftApplication::HOST               => $cluster[RedshiftApplication::ENDPOINT][RedshiftApplication::ADDRESS],
            RedshiftApplication::PORT               => $cluster[RedshiftApplication::ENDPOINT][RedshiftApplication::PORT],
        ]);

    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return resource
     * @throws ApplicationInstallException
     */
    public function getConnection(ApplicationInstall $applicationInstall)
    {
        $settings = $applicationInstall->getSettings();
        $host     = $settings[RedshiftApplication::HOST];
        $port     = $settings[RedshiftApplication::PORT];
        $dbname   = $settings[RedshiftApplication::DBNAME];
        $user     = $settings[RedshiftApplication::MASTER_USER];
        $password = $settings[RedshiftApplication::DB_PASSWORD];

        $connection = pg_connect(
            sprintf('host=%s port=%s dbname=%s user=%s password=%s', $host, $port, $dbname, $user, $password)
        );
        if ($connection === FALSE) {
            throw new ApplicationInstallException('Connection to Redshift db was unsuccessful.');
        }

        return $connection;
    }

}