#!/bin/bash

# ========== Nastavení ==========
HOST="localhost"
PORT="27018"
USER="admin-rs"
PASS=""
AUTH_DB="admin"
LOG_FILE="compact.log"

# ========== Zpracuj argumenty ==========
DRY_RUN=false
OPTIMISE_ALL=false

for arg in "$@"; do
  case "$arg" in
    --dry-run)
      DRY_RUN=true
      echo "🧪 Spouštím v režimu DRY-RUN (compact nebude proveden)"
      ;;
    --optimise-all)
      OPTIMISE_ALL=true
      echo "⚙️  Optimalizace všech databází povolena"
      ;;
    *)
      echo "⚠️ Neznámý parametr: $arg"
      ;;
  esac
done

# ========== Připrav mongo shell příkaz ==========
MONGO="mongosh mongodb://$USER:$PASS@$HOST:$PORT/?authSource=$AUTH_DB&readPreference=secondaryPreferred --quiet"

# ========== Log funkce ==========
log() {
  echo -e "$1" | tee -a "$LOG_FILE"
}

# ========== Zkontroluj, že běžíme na sekundáru ==========
log "🔍 Ověřuji, že běžím na sekundárním uzlu..."
THIS_NODE=$($MONGO --eval "db.serverStatus().host.replace(':$PORT', '')")
NODE_STATE=$($MONGO --eval "rs.status().members.find(m => m.name.includes('$THIS_NODE')).stateStr")

log "👉 Uzel: $THIS_NODE"
log "🩺 Stav: $NODE_STATE"
if [[ "$NODE_STATE" != "SECONDARY" ]]; then
  log "❌ Tento uzel není sekundární. Ukončuji skript."
  exit 1
fi
log "✅ Sekundární uzel detekován. Pokračuji..."

# ========== Projdi databáze ==========
databases=$($MONGO --eval "db.adminCommand('listDatabases').databases.map(db => db.name).filter(name => !['admin','local','config'].includes(name)).join('\n')")

for db in $databases; do
  log "\n📁 Databáze: $db"

  collections=$($MONGO --eval "db.getSiblingDB('$db').getCollectionNames().join('\n')")

  for coll in $collections; do
    output=$($MONGO --eval "
      var s = db.getSiblingDB('$db').getCollection('$coll').stats();
      var ratio = (s.storageSize && s.size) ? (s.storageSize / s.size) : 0;
      print(ratio.toFixed(2));
    ")
    ratio=$(echo "$output" | tail -n 1)

    if [ "$OPTIMISE_ALL" = true ] || awk "BEGIN {exit !($ratio > 1.5)}"; then
      if [ "$DRY_RUN" = true ]; then
        log "  🧪 (dry-run) ➤ $db.$coll (ratio: $ratio)"
      else
        log "  🔧 Compacting ➤ $db.$coll (ratio: $ratio)"
        $MONGO --eval "db.getSiblingDB('$db').runCommand({ compact: '$coll' })" >>"$LOG_FILE" 2>&1
      fi
    else
      log "  ✅ Přeskakuji $db.$coll (ratio: $ratio)"
    fi
  done
done

log "\n✅ Optimalizace dokončena."
if [ "$DRY_RUN" = true ]; then
  log "ℹ️ Spuštěno v režimu dry-run. Pro provedení compact spusť bez parametru --dry-run."
fi
