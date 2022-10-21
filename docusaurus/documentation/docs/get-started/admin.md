import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Orchesty Admin

Orchesty Admin is a user interface that is used for modeling and managing [topologies](../documentation/process-topology), monitoring processes, or authorizing and configuring [applications](../documentation/applications-and-connectors). Admin is available at http://127.0.0.10.

![Admin](/img/documentation/admin.svg)

## Managing users
Admin users are managed using the console.

### Create a user
```shell
bin/orchesty user:create
```

### Delete a user
```shell
bin/orchesty user:delete
```

### Change password
```shell
bin/orchesty user:password:change
```

### List of users
```shell
bin/orchesty user:list
```
