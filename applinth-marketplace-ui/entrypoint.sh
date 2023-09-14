#!/bin/sh

if [ -n "$BRANDING_PACKAGE_URL" ]; then
  echo "[ ----- BRANDING ----- ]"
  echo ""
  echo "Using branding package: ${BRANDING_PACKAGE_URL}"
  mkdir -p /var/www/html/whitelabel
  curl $BRANDING_PACKAGE_URL | lz4cat | tar xv -C /var/www/html/whitelabel

  ## logo
    if [ -f /var/www/html/whitelabel/logo.svg ]; then
      find "/var/www/html/img" -type f -regex ".*/logo.*svg" -exec sh -c 'cat /var/www/html/whitelabel/logo.svg  > {}' \;
      echo "Logo has been replaced."
    fi

  ## favicon
  if [ -f /var/www/html/whitelabel/favicon.ico ]; then
    cp /var/www/html/whitelabel/favicon.ico /var/www/html/favicon.ico
    echo "Favicon has been replaced."
  fi

  ## custom font
  if [ -f /var/www/html/whitelabel/font.css ]; then
    sed " \
        s|</head>|<link rel=\"stylesheet\" href=\"/whitelabel/font.css\"/></head>|g; \
      " -i /var/www/html/index.html
    echo "Custom Font settings has been applied."
  fi

  ## custom styles
  if [ -f /var/www/html/whitelabel/style.css ]; then
    sed " \
        s|</head>|<link rel=\"stylesheet\" href=\"/whitelabel/style.css\"/></head>|g; \
      " -i /var/www/html/index.html
    echo "Custom Styles settings has been applied."
  fi

  echo "Branding has been applied."
  echo ""
fi

if [[ -z "$FRONTEND_URL" || -z "$BACKEND_URL" || -z "$AUTH_BACKLINK" ]]; then
  echo "ERROR: Some ENV variable(s) not defined! See entrypoint.sh"
  exit 1
fi

sed " \
    s|%frontend-base-url-placeholder%|${FRONTEND_URL}|g; \
    s|%api-base-url-placeholder%|${BACKEND_URL}|g; \
    s|%auth-backlink-placeholder%|${AUTH_BACKLINK}|g; \
  " -i /var/www/html/js/*.js
echo "Configurations has been applied."

echo "Starting app..."
exec "$@"
