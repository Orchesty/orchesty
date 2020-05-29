---
layout: main.hbs
collection: documentation
name: Instalace a spuštění PIPES
parent: Tutorials
level: 2
index: 0

lunr: true
tags: installation
---

## Instalace pomocí pipes-skeleton repozitáře

PIPES Skeleton je umístěný veřejně na [GitHubu](https://github.com/hanaboso/pipes-skeleton).

### Stažení skeletonu a inicializace projektu
Nejprve si naklonujeme repozitář příkazem `git clone`, buď pomocí SSH nebo HTTPS. \
Dále je potřeba odstranit podadresář `.git`.
To uděláme příkazem `rm -rf .git` v adresáři, kde máme naklonovaný náš skeleton. 
Posledním krokem je inicializace vlastního projektu pomocí `git init`.

``` bash

git clone git@github.com:hanaboso/pipes-skeleton.git myPipes
cd myPipes
rm -rf .git
git init
```

## Prerekvizity potřebné před spuštěním

K následujícím krokům budete potřebovat spustitelný soubor **make**.

### Linux
Pokud **make** nemáte, pak jej nainstalujete pomocí `apt install make`.

### Mac OS
Pokud **make** nemáte, pak jej nainstalujete pomocí `brew install make`.

Dále je nutné spustit příkaz `sudo ifconfig lo0 alias 127.0.0.10 up`.\
Ten nám vytvoří virtuální síťové rozhranní a PIPES budou moci nastartovat.

### Windows
Pokud **make** nemáte, pak jej nainstalujete pomocí `http://gnuwin32.sourceforge.net/packages/make.htm`.

## Spuštění PIPES aplikace

PIPES spustíme pomocí příkazu `make init-dev`. Tento příkaz nám stáhne Docker image, nastaví databázi a spustí všechny potřebné služby.

Po doběhnutí všech příkazů bude PIPES Admin dostupná na [http://127.0.0.10/ui](http://127.0.0.10/ui). 

## Vytvoření uživatele

Uživatele vytvoříme pomocí `docker-compose exec backend bin/pipes u:c`.