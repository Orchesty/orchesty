#!/usr/bin/env bash

getent passwd dev || groupadd dev -g ${DEV_GID} && useradd -m -u ${DEV_UID} -g ${DEV_GID} dev

# Add Hanaboso gitlab as known host
mkdir -p /home/dev/.ssh
echo "gitlab.hanaboso.net ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDIwOgk1SAbzW3WX/1QaUUqBz8thA+rMVmWVrM0qzYUMW3iRz3AY3QmG6VLj4mF4V4o9KfTXzzoc+lXuykclHfAnSNO5QRQc4xZyYiPliH+Pspt/71HrZJvjCzrZpiJQKVDCB7WKsoBCf/UVv8IL5iw8fdMblniU7ohZJTGeoJ1G0RRd6TlQSznAh4LbfyFT3MFmpL5CYqMibiugnd/ng1/vNdPQnK1rddkunCGLZNJmsqkS3iZHVkxXGYvu8Ao27wiM6iv4OwI1YBdJGsYWTaMB/JgpVDzjwzXM8CaI4Es9mTP/B87R1gFACBJa4NYXdjJi0q4xAyQVwQNgEKoOixp" >> /home/dev/.ssh/known_hosts

exec "$@"
