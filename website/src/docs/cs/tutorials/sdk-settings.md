---
layout: main.hbs
collection: documentation
name: Jak použít vlastní službu s využitím SDK pro přímou integraci s PIPES
parent: Tutorials
level: 2
index: 1

lunr: true
tags: sdk settings
---

Tento návod nás naučí, jak vybudovat vlastní službu, která může obsahovat libovolné rozšíření PIPES. Cokoliv chcete využívat ve svých procesech, můžete pomocí [PIPES SDK](/docs/cs/sdk) vytvářet a provozovat ve vlastních kontejnerech. Jednoduchou registrací v [PIPES Admin](/docs/cs/admin) se pak tyto služby stávají rozšířením PIPES. V takové službě můžete umístit rozšíření aplikací a konektorů [PIPES Appstore](/docs/cs/pipes-appstore), může obsahovat libovolný počet scriptů pro transformaci dat, nebo to může být i jednoúčelová služba, kterou chceme provozovat v samostatném kontejneru.

## Registrace služby do docker-compose

Předpokladem je nainstalovaný Pipes-skeleton dle návodu, Pokud jste ještě tak neučinili přečtěte si [jak nainstalovat PIPES](/docs/cs/installation).

Nyní se přesuňme na Pipes Admin dostupný na [http://127.0.0.10/ui/](http://127.0.0.10/ui/). Otevřeme záložku **SDK Implementations**:

![SDK Implementations](/uploads/scr_sdk_settings/1_pipes_admin_sdk_implementation.png "SDK Implementations")

Klikneme na **Create** a zaregistrujeme SDK jako službu do PIPES:

![Registrace SDK do PIPES](/uploads/scr_sdk_settings/2_pipes_admin_add_sdk.png "Registrace SDK do PIPES")

``` infoBlock
Url vyplnuǰte buď ve tvaru IP adresy nebo hostname.<br> V naší ukázce je url vyplněno jako <strong>php-sdk</strong>. Tento hostname jsme zjistili z docker-compose.yml.
```

Gratulujeme, nyní jsou PIPES připraveny pro budování procesů. Pojďme si vytvořit náš [první process](/docs/cs/tutorials/first-process).