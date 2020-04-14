---
layout: main.hbs
collection: documentation
name: Orchestration
level: 1
index: 3
---

#### Orchestrace

#### Vytvoření procesu a konfigurace
TODO

#### Modelování procesu
TODO

#### Integrace služeb procesu
TODO

#### Plánování
TODO

#### Metriky procesu
Metriky pomáhají identifikovat slabá místa procesu z hlediska výkonu. probíhá sbírání následujících metrik:

Queue Depth - délka fronty zpráv v RabbitMq (delší znamená více věcí na zpracování) \
Waiting Time - čas, než se zpráva začala zpracovávat (menší je lepší) \
Process Time - čas, který zabralo zpracování dané zprávy \
CPU Time - procesorový čas, který zabralo zpracování zprávy \
Request Time - čas, který zabralo dotazování na externí služby \

Všechny metriky až na poslední zmíněnou Request Time jsou sbírány automaticky a není nutné je nějak zapínat či nastavovat. Request Time metriku je potřeba ručně nastavit viz následující příklad v PHP.

<pre class='code'><label>PHP</label><code>
// Vytvoření žádosti o stažení obsahu webové stránky https://example.com
$requestDto = new RequestDto(CurlManager::METHOD_GET, new Uri('https://example.com'));
// Zapnutí request time metriky, která zobrazuje čas nutný pro vykonání požadavku
$requestDto->setDebugInfo($dto);
</code></pre>

#### Logování
Uživatel má možnost logovat informace do ELK stacku nebo MongoDB dle nastavení Pipes Frameworku. Samotné logování může být prováděno ve více úrovních a ty vyšší z nich jsou pak zobrazovány i v uživatelském rozhraní Pipes Frameworku. Pro zobrazení všech podrobných logů je nutné využít dalších nástrojů pro vizualizaci dat, například Kibany v případě ELK stacku.

Využití služeb logování v PHP:

Typickým využím logování (samozřejmě kromě logování chybových stavů) je logování pro potřeby vývoje, kdy se můžeme snadno podívat například jaká data nám vrací externí služba, či jaká data nebo hlavičky poslal předchozí uzel topologie. Viz následující příklad, kdy si zalogujeme hlavičky poslané předchozím uzlem topologie, abychom je mohli vidět.

<pre class='code'><label>PHP</label><code>
namespace Demo\CustomNode;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\Utils\String\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class LoggerCustomNode
 *
 * @package Demo\CustomNode
 */
final class LoggerCustomNode extends CustomNodeAbstract implements LoggerAwareInterface
{

	/**
	* @var LoggerInterface
	*/
	private LoggerInterface $logger;

	/**
	* LoggerCustomNode constructor.
	*/
	public function __construct()
	{
			$this->logger = new NullLogger();
	}

	/**
	* @param LoggerInterface $logger
	*/
	public function setLogger(LoggerInterface $logger): void
	{
			$this->logger = $logger;
	}

	/**
	* @param ProcessDto $dto
	*
	* @return ProcessDto
	*/
	public function process(ProcessDto $dto): ProcessDto
	{
			// Zaloguje hlavičky požadavku do ELK stacku nebo MongoDB dle nastavení a objeví se v Pipes UI
			$this->logger->error(Json::encode($dto->getHeaders()));

			// ...

			return $dto;
	}

}
</code></pre>


#### Notifikace
Uživatel má možnost si v aplikaci zapnout notifikace na různé druhy událostí. Tyto události jsou reprezentovány výčtovým typem NotificationEventEnum, který je možné přetížit a přidat další události. O samotné zpracování notifikací se stará tzv. Status Service, do které přichází vyhodnocení každého procesu a kde je možné na toto vyhodnocení reagovat například odesláním notifikace.

Notifikace jsou konfigurovatelné pomocí následujícího proměnného prostředí:
RABBITMQ_DSN - Connection string pro připojení k RabbitMq serveru
METRICS_SERVICE - Výběr úložiště metrik (InfluxDb, MongoDb)
METRICS_HOST - Hostitel InfluxDb nebo MongoDb databáze
METRICS_PORT - Port InfluxDb nebo MongoDb databáze
REDIS_DSN - Connection string pro připojení k Redis databázi
CRON_DSN - Connection string pro připojení CRON API
FTP_API_DSN - Connection string pro připojení k FTP API
MAILER_API_DSN - Connection string pro připojení k Mailer API
MAPPER_API_DSN - Connection string pro připojení k Mapper API
MONOLITH_API_DSN - Connection string pro připojení k Monolith API
MULTI_PROBE_DSN - Connection string pro připojení k MultiProbe API
STARTING_POINT_DSN - Connection string pro připojení k StartingPoint API
TOPOLOGY_API_DSN - Connection string pro připojení k Topology API
XML_PARSER_API_DSN - Connection string pro připojení k XMl Parser API

METRICS_DSN - Connection string pro připojení k InfluxDb nebo MongoDb serveru (v budoucnu nahradí všechny výše zmíněné METRICS proměnné)

Využití služeb StatusService v PHP:
Typickým využitím notifikací je možnost upozornit uživatele, že při zpracování procesu došlo k nějaké chybě. Více v přiloženém PHP kódu status service, jejíž úkolem je vyhodnotit výstupy všech uzlů procesu a v případě jejich chyb odeslat uživatelům notifikace (pokud je mají povoleny).

<pre class='code'><label>PHP</label><code>
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
</code></pre>