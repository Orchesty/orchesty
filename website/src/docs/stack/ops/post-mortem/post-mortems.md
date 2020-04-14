---
layout: main.hbs
collection: documentation
name: Post Mortems
parent: Ops
level: 2
index: 2
---

# Post mortems

## Incidents

example:

### 2017-12-14 - messages not getting through

#### Symptoms

Messages not gettng through, customer yelling at us

#### Cause

Multibridge instance in topology XY stopped working, process didn't die,
so not restarted.

#### Hotfix

Service restarted manually

#### Solution

* Bug reported to bridge team ([BT-123](#none))
* Added a writeup to the Troubleshooting section