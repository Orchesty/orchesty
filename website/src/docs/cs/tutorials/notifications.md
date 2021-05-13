---
layout: main.hbs
collection: documentation
name: Jak nastavit zasílání notifikací
parent: Tutoriály
level: 2
index: 90

lunr: true
tags: notifications notifikace
lang: cs
---

Využití služeb StatusService v PHP:
Typickým využitím notifikací je možnost upozornit uživatele, že při zpracování procesu došlo k nějaké chybě. Více v přiloženém PHP kódu status service, jejímž úkolem je vyhodnotit výstupy všech uzlů procesu a v případě jejich chyb odeslat uživatelům notifikace (pokud je mají povoleny).

``` PHP
namespace Demo;

use Exception;
use Hanaboso\CommonsBundle\Enum\NotificationEventEnum;
use Hanaboso\NotificationSender\Model\Notification\NotificationManager;
use Hanaboso\Utils\String\Json;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use RabbitMqBundle\Utils\Message;

/**
 * Class StatusServiceCallback
 *
 * @package Demo
 */
final class StatusServiceCallback implements CallbackInterface
{

	/**
	* @var NotificationManager
	*/
	private NotificationManager $manager;

	/**
	* StatusServiceCallback constructor.
	*
	* @param NotificationManager $manager
	*/
	public function __construct(NotificationManager $manager)
	{
			$this->manager = $manager;
	}

	/**
	* @param AMQPMessage $message
	* @param Connection  $connection
	* @param int     	$channelId
	*
	* @throws Exception
	*/
	public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): void
	{
			// Získání obsahu RabbitMq zprávy
			$data = Json::decode(Message::getBody($message));

			// Pro každý uzel topologie vyhodnotí stav zpracování daného uzlu
			foreach ($data['messages'] ?? [] as $innerMessage) {
				// Výsledek zpracování daného uzlu
				$results = [$innerMessage['resultCode'] ?? 0, $innerMessage['originalResultCode'] ?? 0];

				// Pokud daný uzel skončil chybovým stavem 1006, tak odešleme notifikaci
				if (in_array(1_006, $results, TRUE)) {
					// Ve zprávě se nachází jeho kompletní obsah včetně hlaviček, který je možné použít pro složitější logiku
					$this->manager->send(NotificationEventEnum::DATA_ERROR, $innerMessage);
				}
		}

			// Potvrzení zpracování RabbitMq zprávy
			Message::ack($message, $connection, $channelId);
	}

}
```
