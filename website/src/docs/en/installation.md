---
layout: main.hbs
collection: documentation
name: PIPES start and Installation
parent: Getting started
level: 2
index: 1
lang: en
 
lunr: true
tags: installation
---

Thank you for choosing PIPES for your project. The installation is simple, we can handle it in a few steps.
 
## What do we need?
To start and run PIPES, we must have the [Docker](https://www.docker.com/) installed to ensure a virtualized environment. If you don’t have any experience with Docker, we recommend going through its [documentation](https://docs.docker.com/).
 
Next, we`ll need an executable binary **make** file. To install this binary, follow the instructions below:
 
### Linux
Execute the command `apt install make` in a terminal. 

### Mac OS
Install homebrew with `brew install make` command through Terminal. \
Create a virtual network interface using `sudo ifconfig lo0 alias 127.0.0.10 up` command.

### Windows
The installation files can be found here:  
`http://gnuwin32.sourceforge.net/packages/make.htm`.


## Skeleton download & Project initialization 
The basis for the installation is  PIPES-skeleton, which is public on GitHub](https://github.com/hanaboso/pipes-skeleton). 
Clone the repository into the local directory **myPipes**: \
`git clone git@github.com:hanaboso/pipes-skeleton.git myPipes`

Switch to the project folder and delete the subdirectory **.git**: \
`cd myPipes`
`rm -rf .git`

The last step is the initialization of our project by command 
`git init`.

## Startup and login to PIPES Admin

Using `make init-dev` command we start and download the Docker image, which sets up the database and runs all-important services. 


**PIPES Admin** represents the user interface for design and control of processes, its management, and all configuration. 
For more detailed information, we recommend visiting [The introduction of PIPES Admin](/docs/en/admin).

Once installed, PIPES Admin will be available at http://127.0.0.10/ui. Create a new user with `docker-compose exec backend bin/pipes u:c` before first logging on.

Now we can log into the PIPES Admin UI. 
If you’re starting with PIPES, we recommend going through our [tutorials](/docs//tutorials). 

## Other useful links:
- [Itegration with PIPES](/docs/en/integration)
- [Orchestration with PIPES](/docs/en/orchestration)
- [Architecture and development](/docs/en/architecture)
- [PIPES Extensions](/docs/en/extension)


