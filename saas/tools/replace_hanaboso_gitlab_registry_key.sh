#!/bin/bash

SECRET_NAME="hanaboso"
KEY=".dockerconfigjson"
NEW_VALUE=""  # Base64 zakódovaná nová hodnota

if (NEW_VALUE=""); then
  echo "Nezadali jste base 64 zakódovanou novou hodnotu!!!"
  exit 1
fi;

# Získání seznamu všech namespaces
NAMESPACES=$(kubectl get namespaces -o jsonpath='{.items[*].metadata.name}')

# Procházení všech namespaces
for NAMESPACE in $NAMESPACES; do
    # Zkontrolovat, zda secret existuje v aktuálním namespace
    if kubectl get secret $SECRET_NAME -n $NAMESPACE > /dev/null 2>&1; then
        # Aktualizace hodnoty klíče v secretu
        kubectl get secret $SECRET_NAME -n $NAMESPACE -o json | \
        jq --arg key "$KEY" --arg value "$NEW_VALUE" '.data[$key] = $value' | \
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