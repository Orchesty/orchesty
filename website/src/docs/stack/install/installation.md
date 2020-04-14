---
layout: main.hbs
collection: documentation
name: Installation
level: 1 
index: 1
---

#### Instalace
​
#### Minimální systémové požadavky
​
* Obecný Linux s jádrem 4.0, nebo RHEL či CentOS s jádrem 3.10.0-514 (stabilní OverlayFS v2)
* Docker 18.03 CE
* Docker compose 1.19
* 4GB volné RAM
* 50GB volného místa na disku
* 1 CPU jádro 1.8GHz
* Internetové připojení pro stažení docker obrazů (instalace z jiných než veřejných zdrojů zde není popsána, ale je možná)
​
#### Spuštění pomocí nástroje docker-compose
​
Tato varianta je doporučována k testování a jako lokální vývojové prostředí, protože neumožňuje provoz v režimu vysoké dostupnosti.
​
#### Příprava funkčního běhového prostředí
​
* Zajistěte dostupné systémové prostředky a minimální verze komponent systému (viz. [Minimální systémové požadavky]()). Pro instalaci Dockeru a docker-compose použijte repozitáře a správce balíčků vaší Linuxové distribuce, případně následujte oficiální dokumentaci https://docs.docker.com/install/.
* Ověřte, že Docker daemon (dockerd) poslouchá na UNIX socketu na očekávané cestě (standardně `/var/run/docker.sock`). Pokud tomu tak není, zohledněte tento fakt v následujícím kroku "Získání předkonfigurovného docker-compose.yml", doplněním správné cesty (socket může být u některých Linuxových distribucí na jiné než standardní cestě, pro přesné umístění konzultujte dokumentaci Docker balíčku vaší distribuce).
​
```bash
# stat /var/run/docker.sock
  File: '/var/run/docker.sock'
  Size: 0         	Blocks: 0          IO Block: 4096   socket 
  ...
```
​
* Ověře, zda Docker používá overlay2 storage driver (doporučeno).
​
```bash
# docker info 
Containers: 58
 Running: 41
 Paused: 0
 Stopped: 17
Images: 213
Server Version: 18.03.1-ce
Storage Driver: overlay2      <---
 Backing Filesystem: extfs
 Supports d_type: true
 Native Overlay Diff: true
...
```
​
#### Získání předkonfigurovného docker-compose.yml
​
`# nutno doplnit, ještě neumíme`
​
#### Volba pracovního adresáře a příprava spuštění
​
Vyberte cestu, kde chcete aby komponenty Pipes stacku ukládaly svá data a vytvořte prázdný adresář:
​
```bash
# mkdir /srv/pipes
```
​
Do vytvořeného adresáře nakopírujte získaný soubor docker-compose.yml, nebo vytvořte nový a vložte do něj obsah získaného souboru.
​
```bash
# cd /srv/pipes
# cat > docker-compose.yml
---
version: '2'
services:
...
(ctrl+d)
```
​
#### Spuštění Pipes stacku
​
Prvotní vytvoření a spuštění kontejnerů Pipes stacku provedete pomocí nástroje docker-compose:
​
```bash
# cd /srv/pipes
# docker-compose run -d
```
​
#### Inicializace Pipes stacku
​
Po prvním spuštění musí být jednorázově spuštěna inicializace Pipes stacku, při které se vytvoří potřebné datové struktury a správcovský uživatelský účet pro přihlášení do webového rozhraní.
​
```bash
# cd /srv/pipes
# docker-compose run monolith-api bin/nejaky-inicializacni-command
```
​
#### Ověření funkčnosti
​
Pokud při běhu příkazu z předchozího kroku nenastala žádná chyba, mělo by být do minuty na loopback rozhraní dostupné webové rozhraní Pipes.
​
```
# curl http://localhost:8080/ui/
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="shortcut icon" href="/ui/favicon.ico" type="image/x-icon">
	<title>Pipes Manager</title>
...
```
​
**Upozornění**: _Webové rozhraní není z bezpečnostních důvodů v základním nastavení dostupné ze sítě, toto musí být explicitně povoleno při generování docker-compose.yml konfigurátorem, nebo jeho ruční úpravou)_
​
---
​
#### k doplnění
​
* minimalni požadavky: aktualizovat po ověření postupu
* doplnit anchory pro spravnou funkci linků v textu (permalink plugin)
* Získání předkonfigurovného docker-compose.yaml
* Inicializacni command
* Stažení