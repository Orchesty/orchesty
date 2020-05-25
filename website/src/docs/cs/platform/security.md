---
layout: main.hbs
collection: documentation
name: Security
parent: Platform
level: 2
index: 2

lunr: true
tags: security
---

Just random thoughts are collected on this page now, we should base some more conceptual
security manifest on this.

## Notes

* Do not present PHP/Nginx/Apache versions in HTTP headers/responses
* Run all apps as a non-privileged user in containers
  * exceptions are (mainly 3rd party) apps, using privileged master process process (ie. Nginx)
  * How to server APIs on default ports <1024? Usually solved by master process.
* Separate external and internal api gateway (more Nginx instaces)
