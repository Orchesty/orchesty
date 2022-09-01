# Connect SDK service with Orchesty

We'll create a new service which will be integrated into Orchesty.
This way we can build any number of custom services which we can use in our processes.
We can implement connector, data transformation or even applikations within them.

## What do we need?

- Installed Orchesty on localhost from [Installation](../get-started/installation.md). 

:::note for PHP developers
In the Orchesty-skeleton we have a repository with a service that has the Orchesty SDK in its dependencies. The Orchesty PHP SDK is actually a Symfony bundle. So we can use any project built on Symfony to install the necessary dependencies using composer. For a better understanding, we recommend reading the article on [Orchesty architecture and development](../get-started/architecture).
:::

## Service registration
V kořenové složce našeho nového projektu najdeme složky s připravenými aplikacemi, které implementují potřebné SDK. Jako první krok si vybereme aplikaci v Node.js a naučíme ji komunikovat s orchestrační vrstvou. Tato aplikace nám poslouží pro budování konektorů a transformačních scriptů, které budeme používat v rámci našich procesů.

To integrate directly with Orchesty, you can now just register the service inside [Orchesty Admin](../admin/admin.md). This is available in the **Services** tab on the left sidebar or at [http://127.0.0.10/services](http://127.0.0.10/services).

![Services](/img/tutorial/sdk-implementation.png "Services")

Click on **Create** and enter the name of the service and its URL. We can fill in the URL in the form of **IP address** or **hostname** that we have specified in **docker-compose.yml**.

![Registration SDK](/img/tutorial/add-sdk.png "Services")

:::info
Součástí instalace Orchesty jsou připravené skeletony s SDKs pro Node.js a PHP. Výchozí skeleton pro Node.js je v docker-compose registrován na adrese **nodejs-sdk:8080**. Více o struktuře projektu najdete v kapitole [Architecture](../get-started/architecture.md).
:::

## Nastavení hlaviček komunikace
V okně pro registraci služby nebo její úpravu můžete nastavit potřebné hlavičky HTTP komunikace. Lze je využít například pro vložení autorizačního tokenu. Pro náš příklad hlavičky nevyužijeme.

:::tip
Můžete registrovat libovolný počet služeb a orchestrovat tak celou mikroservisní architekturu projektu. SDK usnadní práci a nahradí API orchestrovaných služeb.
:::

Gratulujeme, tímto jsme registrovali první službu do orchestrační vrstvy. V další kapitole vytvoříme první skript nové služby a ukážeme si, jak ho použijeme v novém procesu.
