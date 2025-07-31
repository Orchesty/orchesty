#!/bin/bash

# Použití
if [[ $# -ne 2 ]]; then
  echo "Použití: $0 <username> <token>"
  exit 1
fi

SECRET_NAME="hanaboso"
KEY=".dockerconfigjson"
USERNAME="$1"
TOKEN="$2"

DOCKER_SECRET=$(kubectl create secret docker-registry muj-secret \
  --docker-server=dkr.hanaboso.net \
  --docker-username="$USERNAME" \
  --docker-password="$TOKEN" \
  --dry-run=client -o json | jq -r '.data[".dockerconfigjson"]')

# Získání seznamu všech namespaces
NAMESPACES=$(kubectl get namespaces -o jsonpath='{.items[*].metadata.name}')

# Procházení všech namespaces
for NAMESPACE in $NAMESPACES; do
    # Zkontrolovat, zda secret existuje v aktuálním namespace
    if kubectl get secret $SECRET_NAME -n $NAMESPACE > /dev/null 2>&1; then
        # Aktualizace hodnoty klíče v secretu
        kubectl get secret $SECRET_NAME -n $NAMESPACE -o json | \
        jq --arg key "$KEY" --arg value "$DOCKER_SECRET" '.data[$key] = $value' | \
        kubectl apply -f -

        if [ $? -eq 0 ]; then
            echo "Secret $SECRET_NAME v namespace $NAMESPACE byl úspěšně aktualizován."
        else
            echo "Nepodařilo se aktualizovat secret $SECRET_NAME v namespace $NAMESPACE."
        fi
    else
        echo "Secret $SECRET_NAME v namespace $NAMESPACE neexistuje."
    fi
done