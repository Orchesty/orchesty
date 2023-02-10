import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Logs

For logging we use `logger`. We can use logs of type `info` and `error`.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
logger.info('some log message', dto);
logger.error('some log message', dto);
```
</TabItem>
</Tabs>

## Logs in Admin

Adding another parameter with value `true` will save the log message to the database for display in the Admin.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
logger.info('some log message', dto, true);
```
</TabItem>
</Tabs>

![Logs](/img/documentation/logs.svg "Logs")

:::tip
Logs in Admin are mainly suitable for logging the business logic of processes. The log view in admin is not intended to supersede developer tools for reading logs.
:::
