<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift;

use Aws\Redshift\RedshiftClient;
use Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\AwsApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use PgSql\Connection;

/**
 * Class RedshiftApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\AmazonApps\Redshift
 */
final class RedshiftApplication extends AwsApplicationAbstract
{

    public const ENDPOINT    = 'Endpoint';
    public const DB_PASSWORD = 'DbPassword';

    private const HOST               = 'host';
    private const PORT               = 'Port';
    private const DBNAME             = 'DBName';
    private const ADDRESS            = 'Address';
    private const MASTER_USER        = 'MasterUsername';
    private const CLUSTER_IDENTIFIER = 'ClusterIdentifier';

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'redshift';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
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
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField(new Field(Field::TEXT, self::KEY, 'Key', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::SECRET, 'Secret', NULL, TRUE))
            ->addField(new Field(Field::PASSWORD, self::DB_PASSWORD, 'Database Password', NULL, TRUE))
            ->addField((new Field(Field::SELECT_BOX, self::REGION, 'Region', '', TRUE))->setChoices(self::REGIONS));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        if (!isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM])) {
            return FALSE;
        }

        $settings = $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM];

        return isset($settings[self::KEY])
            && isset($settings[self::SECRET])
            && isset($settings[self::REGION])
            && isset($settings[self::DB_PASSWORD]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return RedshiftClient
     */
    public function getRedshiftClient(ApplicationInstall $applicationInstall): RedshiftClient
    {
        $settings = $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM];

        return new RedshiftClient(
            [
                self::CREDENTIALS => [
                    self::KEY    => $settings[self::KEY],
                    self::SECRET => $settings[self::SECRET],
                ],
                self::REGION      => $settings[self::REGION],
                self::VERSION     => self::LATEST,
            ],
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $settings
     *
     * @return ApplicationInstall
     * @throws ApplicationInstallException
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        $applicationInstall = parent::saveApplicationForms($applicationInstall, $settings);
        $cluster            = $this->getRedshiftClient($applicationInstall)->describeClusters()->get('Clusters')[0];

        if (!$cluster) {
            throw new ApplicationInstallException('Login into application was unsuccessful.');
        }

        return $applicationInstall->setSettings(
            [
                self::CLUSTER_IDENTIFIER => $cluster[self::CLUSTER_IDENTIFIER],
                self::DBNAME             => $cluster[self::DBNAME],
                self::DB_PASSWORD        => $applicationInstall
                                                               ->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::DB_PASSWORD],
                self::HOST               => $cluster[self::ENDPOINT][self::ADDRESS],
                self::MASTER_USER        => $cluster[self::MASTER_USER],
                self::PORT               => $cluster[self::ENDPOINT][self::PORT],
            ],
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return Connection
     * @throws ApplicationInstallException
     */
    public function getConnection(ApplicationInstall $applicationInstall): Connection
    {
        $settings = $applicationInstall->getSettings();
        $host     = $settings[self::HOST];
        $port     = $settings[self::PORT];
        $dbname   = $settings[self::DBNAME];
        $user     = $settings[self::MASTER_USER];
        $password = $settings[self::DB_PASSWORD];

        $connection = pg_connect(
            sprintf('host=%s port=%s dbname=%s user=%s password=%s', $host, $port, $dbname, $user, $password),
        );
        if ($connection === FALSE) {
            throw new ApplicationInstallException('Connection to Redshift db was unsuccessful.');
        }

        return $connection;
    }

}
