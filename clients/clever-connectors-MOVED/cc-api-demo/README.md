NETTE DEMO FOR CLEVER CONNECTORS
================================

LOCAL DEVELOPMENT
-----------------
* Create local config
```bash
cp -f app/config/config.local.neon.dist app/config/config.local.neon
```
* Create cert
```bash
openssl pkcs12 -in stage-staff.p12 -out stage-staff.pem -nodes
```
* Copy certificate for stage to cert folder
* Run app
```bash
make init
```
* Go to 127.0.0.44/api-demo