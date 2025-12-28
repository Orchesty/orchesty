# Topology Editor - Actions Management

## Overview

Tento dokument popisuje, jak fungují akce v Topology Editor a jak jsou integrovány s aplikací.

## Akce v Editoru

### Současný stav

Editor momentálně používá **interní systém akcí**, který je součástí `rete-editor` knihovny. Tyto akce jsou dostupné v:
- Context menu (pravý klik na canvas)
- Node palette menu (tlačítko "Add Node")

### Mock Data

Všechny dostupné akce pro editor jsou definovány v:
```typescript
/src/assets/mock-data/actions.ts
```

Tento soubor obsahuje:
- `ActionOption` type - definice struktury akce
- `allActionOptions` - pole všech dostupných akcí
- `getAvailableActions(nodeType)` - funkce pro filtrování akcí podle typu
- `getAllActions()` - funkce pro získání všech akcí

### Service Layer

Pro budoucí integraci s backendem je připravený service:
```typescript
/src/services/topologyEditorService.ts
```

Tento service poskytuje API pro:
- `getAllActions()` - získání všech akcí
- `getActionsByType(type)` - filtrování podle typu (custom/connector/batch)
- `searchActions(query)` - vyhledávání akcí podle názvu nebo workeru

## Typy Akcí

Editor podporuje tři typy akcí:

### 1. Custom Actions
Vlastní akce pro zpracování dat
- Příklad: "Process Data", "Transform JSON", "Validate Input"

### 2. Connector Actions
Akce pro připojení k externím systémům
- Příklad: "Magento Get Product", "Shopify Create Order"
- Mohou obsahovat vlastní ikony (SVG)

### 3. Batch Actions
Akce pro hromadné zpracování
- Příklad: "List All Products", "Bulk Update"

## Struktura Akce

```typescript
{
  name: string        // Název akce
  worker: string      // Název workeru, který akci vykonává
  type: 'custom' | 'connector' | 'batch'
  icon?: string       // Volitelná SVG ikona (pro connectors)
}
```

## Budoucí Integrace

V produkční verzi budou akce načítány dynamicky z backendu:

1. **Při otevření editoru**: Načtení všech dostupných akcí
2. **Context menu**: Zobrazení akcí relevantních pro vybraný typ nodu
3. **Vyhledávání**: Možnost vyhledat konkrétní akci
4. **Refresh**: Možnost znovu načíst akce z backendu

## Použití v Komponentě

```vue
<script setup lang="ts">
import { topologyEditorService } from '@/services/topologyEditorService'

// Získání všech akcí
const actions = await topologyEditorService.getAllActions()

// Získání akcí podle typu
const customActions = await topologyEditorService.getActionsByType('custom')

// Vyhledávání akcí
const foundActions = await topologyEditorService.searchActions('magento')
</script>
```

## Poznámky

- Editor používá stejná mock data (`data-stream.json`) v edit i readonly režimu
- Akce jsou dostupné pouze v **edit režimu**
- V readonly režimu je editace zakázána a akce nejsou dostupné
- Pro přidání nové akce do mocků je třeba ji přidat do `actions.ts`

