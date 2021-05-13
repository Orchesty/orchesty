---
layout: main.hbs
collection: documentation
name: Jak integrovat službu s využitím webhooks
parent: Tutoriály
level: 2
index: 70

lunr: true
tags: webhooks webhooky
lang: cs
---

V tomto návodu si ukážeme, jak rozšířit aplikaci tak, aby umožnila práci s webhooky. Jako základ nám poslouží aplikace HubSpot. Pokud aplikaci nemáte podívejte se nejdříve na tutoriál [Jak vytvořit aplikaci s autorizací OAuth 2](/docs/cs/tutorials/oauth2-application).

## Co je to Webhook?

V v terminologii vývoje webových aplikací je Webhook změna nebo rozšíření chování webové aplikace pomocí vlastních zpětných volání (callbacků). Registrací Webhooku je myšleno vytvoření požadavku na systém reagovat na konkrétní událost. Typicky se jedná o události změny stavů entit daného systému.
Webhook se obvykle registruje se dvěma parametry: Eventem a URL. Event je ona událost, na kterou chceme reagovat (npř. vytvoření uživatele, update objednávky, ...). URL je cesta, kam má daný systém zavolat, když taková akce nastane. 
Pokud tato akce nastane, systé vyvolá tzv. Hook a odešle data na zadanou adresu. Formát dat je převážně JSON a požadavek se odesílá pomocí http POST metody.

## Implementace WebhookApplicationInterface

Aplikace, která umožnuje využívat Webhooky, musí implementovat rozhraní ``WebhookApplicationInterface``. 
Aplikace po úpravě bude vypadat následovně:

``` PHP 1

use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;

final class HubSpotApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    ... original codes ...

}
```

Nyní musíme ve třídě aplikace doimplementovat chybějící metody definované přidaným rozhraním.

### Definice Webhooků

Metoda ``getWebhookSubscriptions`` definuje, které Webhooky bude aplikace využívat. Pro naši ukázku chceme, aby HubSpot posílal Webhooky na vytvoření nebo odstranění kontaktů.
 
``` PHP 2

public function getWebhookSubscriptions(): array
{
    return [
        new WebhookSubscription('Create Contact', 'Webhook', '', ['name' => 'contact.creation']),
        new WebhookSubscription('Delete Contact', 'Webhook', '', ['name' => 'contact.deletion']),
    ];
}
```

### Složení požadavku pro registraci Webhooku

Pro zaregistrování Webhooku v HubSpotu je potřeba složit objekt ``requestDto``. K tomu slouží metoda ``getWebhookSubscribeRequestDto``. V našem případě bude vypadat následovně:

``` PHP 3

public function getWebhookSubscribeRequestDto(
    ApplicationInstall $applicationInstall,
    WebhookSubscription $subscription,
    string $url
): RequestDto
{
    $hubspotUrl = sprintf(
        '%s/webhooks/v1/%s/subscriptions',
        self::BASE_URL,
        $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::APP_ID]
    );
    $body       = Json::encode(
        [
            'webhookUrl'          => $url,
            'subscriptionDetails' => [
                'subscriptionType' => $subscription->getParameters()['name'],
                'propertyName'     => 'email',
            ],
            'enabled'             => FALSE,
        ]
    );

    return $this->getRequestDto($applicationInstall, CurlManager::METHOD_POST, $hubspotUrl, $body);
}
```

### Vyhodnocení registrace Webhooku

Po dokončení registrace Webhooku je potřeba ještě uložit ID Webohooku, tak aby se dal Webhook zase odebrat. 

``` PHP 4

public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
{
     return $dto->getJsonBody()['id'];
}
```

### Složení požadavku pro odebrání Webhooku

Stejně jako pro registraci je potřeba složit požadavek i pro odebrání Webhooku z HubSpotu.

``` PHP 5

public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto
{
    $url = sprintf(
        '%s/webhooks/v1/%s/subscriptions/%s',
        self::BASE_URL,
        $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::APP_ID],
        $id
    );

    return $this->getRequestDto($applicationInstall, CurlManager::METHOD_DELETE, $url);
}
```

### Vyhodnocení požadavku pro odebrání Webhooku

Po odebrání ještě ověříme, že náš požadavek proběhl správně.

``` PHP 6

public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
{
    return $dto->getStatusCode() === 204;
}
```

Posledním krokem je změna aplikace a typ WebhookApplication. To zajistíme přetížením metody ``getApplicationType``.

``` PHP 7

public function getApplicationType(): string
{
    return ApplicationTypeEnum::WEBHOOK;
}
```

## AppStore

Přejděme nyní do PIPES Admin na záložku [AppStore](http://127.0.0.10/ui/app_store), detail aplikace **HubSpot**. Nyní vidíme, že ve formuláři přibyly naše dva Webhooky definované metodou ``getWebhookSubscriptions``.

![](/uploads/scr_webhook/1_webhook_app.png "HubSpot application - Webhooks")

## Webhook konektor

Příchozí Webhook je potřeba zpracovat. K tomu bude sloužit konektor, který využívá místo metody ``processAction`` metodu ``processEvent``. Konektor pak bude vypadat následovně:

``` PHP 8

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;

final class HubSpotContactCreatedConnector extends ConnectorAbstract
{

    use ProcessActionNotSupportedTrait;

    public function getId(): string
    {
        return 'hub-spot.contact-created';
    }

    public function processEvent(ProcessDto $dto): ProcessDto
    {
        return $dto;
    }
}
```

A nezapomeneme konektor zaregistrovat.

``` YAML 9

# ./config/connector/connector.yaml
service:
    hbpf.connector.hub-spot.contact-created:
        class: Pipes\PhpSdk\Connector\HubSpot\HubSpotContactCreatedConnector
        calls:
            - ['setApplication', ['@hbpf.application.hub-spot']]
```

## Testování

Abychom mohli otestovat tuto funkčnost, je nutné vytvořit topologii. Topologii pojmenujeme třeba **hubspot-contact-created** Topologie bude vypadat následovně:
![](/uploads/scr_webhook/2_webhook_topology.png "Webhook - topologie")

Nyní přiřadíme kódy k jednotlivým uzlům.
![](/uploads/scr_webhook/3_webhook_topology.png "Webhook - topologie")

Topologii uložíme, publikujeme a enablujeme. Poté se přesuneme zpět do Appstoru na detail HubSpot aplikace.
Pro zaregistrování Webhooků nám stačí už jen vyplnit jméno topologie, kterou chceme volat, když HubSpot odešle požadavek.

Zadáme **hubspot-contact-created** jako jméno topologie a potvrdíme. Výsledek vypadá následovně:

![](/uploads/scr_webhook/4_webhook_subscribed.png "Webhook - subscribed")

Pokud vytvoříme nový kontakt v Hubspotu (ať už pomocí naší topologie nebo ručně přes webové rozhraní) HubSpot odešle požadavek s daty. Tato data pak nalezneme v záložce [Human Tasks v Pipes Admin](http://127.0.0.10/ui/human_tasks).

![](/uploads/scr_webhook/5_webhook_data.png "Webhook - příchozí data")


Gratulujeme, právě jste se naučili, jak implementovat Webhooky ve vaší aplikaci.