#!/usr/bin/env bash

DEV_UID=$(id -u)
DEV_GID=$(id -g)

su-exec root addgroup -g ${DEV_GID} dev
su-exec root adduser -u ${DEV_UID} -D -G dev dev
su-exec root chown -R dev:dev /var/www /home/dev /opt

export HOME=/home/dev

# Add Hanaboso gitlab as known host
mkdir -p $HOME/.ssh
echo "gitlab.hanaboso.net ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDIwOgk1SAbzW3WX/1QaUUqBz8thA+rMVmWVrM0qzYUMW3iRz3AY3QmG6VLj4mF4V4o9KfTXzzoc+lXuykclHfAnSNO5QRQc4xZyYiPliH+Pspt/71HrZJvjCzrZpiJQKVDCB7WKsoBCf/UVv8IL5iw8fdMblniU7ohZJTGeoJ1G0RRd6TlQSznAh4LbfyFT3MFmpL5CYqMibiugnd/ng1/vNdPQnK1rddkunCGLZNJmsqkS3iZHVkxXGYvu8Ao27wiM6iv4OwI1YBdJGsYWTaMB/JgpVDzjwzXM8CaI4Es9mTP/B87R1gFACBJa4NYXdjJi0q4xAyQVwQNgEKoOixp" >> $HOME/.ssh/known_hosts

su-exec root mkdir -p /opt/phpstorm-coverage
su-exec root chmod -R 774 /var/www/var/log && su-exec root chown -R dev:dev /var/www/var/log
su-exec root chmod -R 774 /var/www/var/cache && su-exec root chown -R dev:dev /var/www/var/cache
su-exec root chmod -R 774 /opt/phpstorm-coverage && su-exec root chown -R dev:dev /opt/phpstorm-coverage

exec "$@"
