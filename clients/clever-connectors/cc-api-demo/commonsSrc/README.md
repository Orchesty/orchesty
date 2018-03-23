Clever Commons Extensions
==============

Push into **'CleverCore\Commons'** repository


### Configuration
```
extensions:
    commons: CleverCore\Commons\DI\CommonsExtension

commons:
	base_uri: ::getenv('CC_API_BACKEND')
	cert: %appDir%/../cert/cert.pem
```