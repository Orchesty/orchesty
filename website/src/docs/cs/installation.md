---
layout: main.hbs
collection: documentation
name: Instalace a spuštění PIPES
parent: Getting started
level: 2
index: 1
 
lunr: true
tags: instalace
---
 
Děkujeme, že jste se rozhodli využít PIPES. Instalace je jednoduchá a zvládneme ji v pár krocích.
 
## Co budeme potřebovat?
Pro spuštění a běh PIPES je nutné mít nainstalovaný [Docker](https://www.docker.com/), který zajistí virtualizované prostředí. Pokud s Dockerem nemáte zkušenosti, doporučujeme nejprve [prostudovat dokumentaci](https://docs.docker.com/).
 
Dále budeme potřebovat spustitelný **make** soubor. Pokud jej nemáte nainstalovaný, postupujte podle následujícího návodu:
### Linux
V konzoli zadáme příkaz `apt install make`.
 
### Mac OS
V konzoli spustíme pomocí `brew install make`.
 
### Windows
Instalační soubory nalezneme zde: `http://gnuwin32.sourceforge.net/packages/make.htm`.
 
 
 
 
## Stažení skeletonu a inicializace projektu
Základem pro instalaci je PIPES skeleton, který je umístěný veřejně na [GitHub](https://github.com/hanaboso/pipes-skeleton). Nejprve naklonujeme repozitář do lokálního adresáře **myPipes**: \
`git clone https://github.com/hanaboso/pipes-skeleton.git myPipes`
 
Přepneme se do složky projektu a odstraníme podadresář **.git**: \
`cd myPipes` \
`rm -rf .git`
 
Posledním krokem je inicializace vlastního projektu příkazem
`git init`.
 
 
## Spuštění a přihlášení do PIPES Admin
 
Pomocí příkazu `make init-dev` spustíme a stáhneme Docker image, který nastaví databázi a spustí všechny potřebné služby.
 
Aplikace **PIPES Admin** představuje uživatelské rozhraní PIPES, které slouží pro návrh a řízení procesů, pro jejich správu a veškerou konfiguraci. Pro bližší informace doporučujeme nahlédnout do [Představení PIPES Admin](/docs/cs/admin).
 
Po instalaci bude aplikace **PIPES Admin** dostupná na [http://127.0.0.10/ui](http://127.0.0.10/ui). Před prvním přihlášením je nutné  vytvořit nového uživatele pomocí příkazu `docker-compose exec backend bin/pipes u:c`.
 
Nyní se můžeme do uživatelského rozhraní [PIPES Admin](http://127.0.0.10/ui) přihlásit. Pokud s PIPES začínáte, doporučujeme nastudovat [naše návody](/docs/cs/tutorials).
 
## Další užitečné odkazy:
- [Vše o integracích s PIPES](/docs/cs/integration)
- [Orchestrace s PIPES](/docs/cs/orchestration)
- [Architektura a development](/docs/cs/architecture)
- [Vlastní rozšíření PIPES](/docs/cs/extention)
