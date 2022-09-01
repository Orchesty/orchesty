import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Installation and start-up

Thank you for choosing Orchesty for your project. The installation is simple, we can handle it in few steps.
 
## What do we need?
To start and run Orchesty, we must have the [Docker](https://www.docker.com/) installed to ensure a virtualized environment. If you don’t have any experience with Docker, we recommend going through its [documentation](https://docs.docker.com/).

Next, we`ll need an executable binary **make** file. To install this binary, follow the instructions below:

<Tabs
    defaultValue="linux"
    values={[
        {label: 'Linux', value: 'linux'},
        {label: 'MacOS', value: 'mac'},
        {label: 'Windows', value: 'win'},
    ]}
>
<TabItem value="linux">
Execute following command in terminal   

```bash
apt install make
```
</TabItem>
<TabItem value="mac">
Install homebrew with command through Terminal.

```bash
brew install make
```
Then create a virtual network interface.
```bash
sudo ifconfig lo0 alias 127.0.0.10 up
```
</TabItem>
<TabItem value="win">

[Download](http://gnuwin32.sourceforge.net/packages/make.htm) installation files and follow associated manual.

</TabItem>
</Tabs>

## Skeleton download & Project initialization
The basis for the installation is Orchesty-skeleton, which is public on [GitHub](https://github.com/Orchesty/orchesty-skeleton).  

<Tabs
    defaultValue="git"
    values={[
        {label: 'GIT', value: 'git'},
        {label: 'ZIP', value: 'zip'},
]}
>
<TabItem value="git">

Clone the repository into the local directory **myOrchesty**:  

```shell
git clone https://github.com/Orchesty/orchesty-skeleton.git myOrchesty
```

Or if we want our **tutorial codes**:

```shell
git clone https://github.com/Orchesty/orchesty-tutorial.git myOrchesty
```


Navigate into project folder and delete the subdirectory **.git**:  
```shell
cd myOrchesty
rm -rf .git
```

The last step is the initialization of our project by command.
```shell
git init
```
</TabItem>
<TabItem value="zip">

**Download** and extract whole skeleton from:

https://github.com/Orchesty/orchesty-skeleton/archive/refs/heads/master.zip

Or if we want our **tutorial codes**:

https://github.com/Orchesty/orchesty-tutorial/archive/refs/heads/master.zip

</TabItem>
</Tabs>

## Startup and login into Orchesty Admin

Using `make init-dev` command we start and download the Docker image, which sets up the database and starts all important services.

**Orchesty Admin** represents the user interface for design and control of processes, it's management, and related configurations.
For more detailed information, we recommend visiting [The introduction of Orchesty Admin](../admin/admin.md).

Once installed, admin will be available at http://127.0.0.10.
Create a new user before first logging in. 
```shell
docker-compose exec backend bin/pipes u:c
```

Now we can log into the UI.
If you’re starting with Orchesty, we recommend going through our [tutorials](../tutorials/getting-started-with-tutorials.md).

