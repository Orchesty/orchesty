user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

env PHP_APP_INDEX;
env PHP_WEBROOT;

events {
	worker_connections 768;
	# multi_accept on;
}

http {

	##
	# Basic Settings
	##

	sendfile on;
	tcp_nopush on;
	tcp_nodelay on;
	keepalive_timeout 65;
	types_hash_max_size 2048;
	# server_tokens off;

	# server_names_hash_bucket_size 64;
	# server_name_in_redirect off;

	include /etc/nginx/mime.types;
	default_type application/octet-stream;

	##
	# SSL Settings
	##

	ssl_protocols TLSv1 TLSv1.1 TLSv1.2; # Dropping SSLv3, ref: POODLE
	ssl_prefer_server_ciphers on;

	##
	# Logging Settings
	##

	access_log /proc/self/fd/1;
	error_log /proc/self/fd/2;

	##
	# Gzip Settings
	##

	gzip on;
	gzip_disable "msie6";

	# gzip_vary on;
	# gzip_proxied any;
	# gzip_comp_level 6;
	# gzip_buffers 16 8k;
	# gzip_http_version 1.1;
	# gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

	##
	# Virtual Host Configs
	##

	include /etc/nginx/conf.d/*.conf;

	# resolve_ prefix before any hostname causes it to be periodicaly replaced
	# with its current IP address by upstream_resolver.sh

	upstream monolith-api-upstream {
		server resolve_monolith-api;
		keepalive 64;
	}

	upstream notification-sender-fpm-upstream {
		server resolve_notification-sender-fpm:9000;
		keepalive 64;
	}

	upstream notification-center-fpm-upstream {
		server resolve_notification-center-fpm:9000;
		keepalive 64;
	}

	server {
		server_name _;
		listen 80 default_server;

		set_by_lua $php_app_index 'return os.getenv("PHP_APP_INDEX")';
		set_by_lua $php_webroot 'return os.getenv("PHP_WEBROOT")';

		location /ui {
			root /var/www/html;
			rewrite ^/ui$ /ui/ permanent;
			try_files $uri /ui/index.html =404;
		}

		location /grafana/ {
			proxy_pass http://resolve_grafana:3000/;
		}

		location /spitter-api {
			proxy_pass http://resolve_spitter-api:3000/;
		}

		location /cron-api {
			proxy_pass http://resolve_cron-api:5000/;
		}

		location /socket.io {
			proxy_pass http://resolve_stream;
			proxy_http_version 1.1;
			proxy_set_header Upgrade $http_upgrade;
			proxy_set_header Connection "upgrade";
		}

		# FCGI api-demo route
		location /api-demo {
			include fastcgi_params;
			fastcgi_param SCRIPT_NAME index.php;
			fastcgi_param SCRIPT_FILENAME /var/www/html/www/index.php; # TODO: make non-CM specific
			fastcgi_param PATH_INFO $request_uri;
			fastcgi_pass resolve_api-demo-fpm:9000;
		}

		location /notification-sender {
			set $path_info "/";
			if ($request_uri ~* ^/notification-sender(/.+)$) {
				set $path_info $1;
			}

			include fastcgi_params;
			fastcgi_param SCRIPT_NAME index.php;
			fastcgi_param SCRIPT_FILENAME /srv/project/public/index.php; # TODO: make non-CM specific
			fastcgi_param PATH_INFO $path_info;
			fastcgi_param REQUEST_URI $path_info;
			fastcgi_keep_conn on;
			fastcgi_pass notification-sender-fpm-upstream;
		}

		location /notification-center {
			set $path_info "/";
			if ($request_uri ~* ^/notification-center(/.+)$) {
				set $path_info $1;
			}

			include fastcgi_params;
			fastcgi_param SCRIPT_NAME index.php;
			fastcgi_param SCRIPT_FILENAME /srv/project/public/index.php; # TODO: make non-CM specific
			fastcgi_param PATH_INFO $path_info;
			fastcgi_param REQUEST_URI $path_info;
			fastcgi_keep_conn on;
			fastcgi_pass notification-center-fpm-upstream;
		}

		# monolith route (API fallback)
		location / {
			proxy_pass http://monolith-api-upstream;
		}
	}
}
