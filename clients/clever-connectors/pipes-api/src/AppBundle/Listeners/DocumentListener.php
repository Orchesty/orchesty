<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Tomas Sedlacek
 * Mail: mail@kedlas.cz
 * Date: 29/09/2017
 * Time: 11:18
 */

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Document\Settings;
use CleverConnectors\AppBundle\Document\SystemInstall;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreFlushEventArgs;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;

/**
 * TODO - DRY - pf-bundles/src/Authorization/DocumentListener/DocumentListener.php
 * TODO - replace these listeners by crypting implementation inside Document classes on preFlush and postLoad events
 *
 * Class DocumentListener
 *
 * @package CleverConnectors\AppBundle\Listeners
 */
class DocumentListener
{

    /**
     * @var CryptManager
     */
    private $cryptManager;

    /**
     * DocumentListener constructor.
     *
     * @param CryptManager $cryptManager
     */
    function __construct(CryptManager $cryptManager)
    {
        $this->cryptManager = $cryptManager;
    }

    /**
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event): void
    {
        for ($i = 0; $i <= 1; $i++) {
            $documents = $i
                ? $event->getDocumentManager()->getUnitOfWork()->getScheduledDocumentUpdates()
                : $event->getDocumentManager()->getUnitOfWork()->getScheduledDocumentInsertions();

            /** @var SystemInstall $document */
            foreach ($documents as $document) {
                if ($this->isSettings($document)) {
                    $raw = $document->getSettings();
                    $encrypted = new Settings(
                        $this->cryptManager->encrypt($raw->getUsername()),
                        $this->cryptManager->encrypt($raw->getPassword()),
                        $this->cryptManager->encrypt($raw->getAccessToken()),
                        $raw->getRedirectUrl()
                    );

                    $document->setSettings($encrypted);
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $document = $event->getDocument();

        /** @var SystemInstall $document */
        if ($this->isSettings($document)) {
            $encrypted = $document->getSettings();
            $raw = new Settings(
                $this->cryptManager->decrypt($encrypted->getUsername()),
                $this->cryptManager->decrypt($encrypted->getPassword()),
                $this->cryptManager->decrypt($encrypted->getAccessToken()),
                $encrypted->getRedirectUrl()
            );

            $document->setSettings($raw);
        }
    }

    /**
     * @param mixed $document
     *
     * @return bool
     */
    private function isSettings($document): bool
    {
        return $document instanceof Settings;
    }

}