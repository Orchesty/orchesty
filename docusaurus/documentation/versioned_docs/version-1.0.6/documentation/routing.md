import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Routing

When node has more than one follower, you can specify which of them should receive and continue to process this message.
Message is by default sent to all followers and thus duplicated for each one of them.

![Routing](/img/documentation/routing.svg)

To name desired followers you have to call `setForceFollowers` method providing it with names of nodes.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setForceFollowers('some-connector', 'another-connector');
```
</TabItem>
</Tabs>



