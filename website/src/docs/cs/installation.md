---
layout: main.hbs
collection: documentation
name: Instalace a spuštění PIPES
level: 1
index: 1

lunr: true
tags: installation
---

Základem pro instalaci je PIPES skeleton, který je umístěný veřejně na [GitHubu](https://github.com/hanaboso/pipes-skeleton). Instalace PIPES je opravdu jednoduchá a zvládneme ji v pár krocích. 

## Co budeme potřebovat?
Pro spuštění PIPES budeme potřebovat nainstalovaný [Docker](https://www.docker.com/), který nám zajistí virtualizované prostředí pro běh PIPES. Pokud Docker nemáte a neumíte s ním pracovat, doporučujeme nejprve [prostudovat jejich dokumentaci](https://docs.docker.com/).

Dále budeme potřebovat spustitelný soubor **make**. Pokud jej nemáte nainstalovaný,  postupujte podle následujícího návodu:
 
### Linux
V konzoli zadáme příkaz `apt install make`.

### Mac OS
Instalaci spustíme v konzoli pomocí `brew install make`. \
Dále vytvoříme virtuální síťové rozhraní příkazem `sudo ifconfig lo0 alias 127.0.0.10 up`.

### Windows
Instalační soubory najdeme zde: `http://gnuwin32.sourceforge.net/packages/make.htm`.




## Stažení skeletonu a inicializace projektu
Nejprve naklonujeme repozitář do lokálního adresáře **myPipes**: \
`git clone git@github.com:hanaboso/pipes-skeleton.git myPipes`

Přepneme se do složky projektu a odstraníme podadresář **.git**: \
`cd myPipes` \
`rm -rf .git`

Posledním krokem je inicializace vlastního projektu příkazem
`git init`.


## Spuštění a přihlášení do PIPES Admin

PIPES spustíme pomocí příkazu `make init-dev`. Tento příkaz nám stáhne Docker image, nastaví databázi a spustí všechny potřebné služby.

**PIPES Admin** je aplikace, která představuje uživatelské rozhraní PIPES. Slouží pro návrh a řízení procesů, pro jejich správu a veškerou konfiguraci. Pro bližší informace doporučujeme nahlédnout do [Představení PIPES Admin](/docs/cs/admin). 

Po doběhnutí instalace bude aplikace **PIPES Admin** dostupná na [http://127.0.0.10/ui](http://127.0.0.10/ui). Než se poprvé přihlásíme, musíme ještě vytvořit nového uživatele. Vytvoříme ho pomocí příkazu `docker-compose exec backend bin/pipes u:c`. 

To je vše. Nyní se můžeme přihlásit do uživatelského rozhraní [PIPES Admin](http://127.0.0.10/ui). Pokud se teprve seznamujete s PIPES, doporučujeme nastudovat [naše návody](/docs/cs/tutorials). 

### Další užitečné odkazy:
- [Vše o integracích s PIPES](/docs/cs/integration)
- [Orchestrace s PIPES](/docs/cs/orchestration)
- [Architektura a deployment](/docs/cs/architecture)
- [Vlastní rozšíření PIPES](/docs/cs/extention)

