# abraflexi-ipex (Česky)

Tento dokument popisuje českou dokumentaci k integraci IPEX ↔ AbraFlexi se zaměřením na logiku minimální fakturace.

## Logika minimální fakturace (Invoicing Threshold)

Aby se nevystavovaly zbytečně malé faktury, integrace používá konfigurovatelný minimální limit (threshold) pro vystavení faktury.

### Hlavní principy

- `ABRAFLEXI_MINIMAL_INVOICING` – minimální částka nutná k vystavení faktury (výchozí 50.00)
- Objednávky ve stavu `stavDoklObch.pripraveno` obsahující produkt `ABRAFLEXI_PRODUCT` (default `IPEX_POSTPAID`) se seskupí podle zákazníka (`firma`).
- Sečte se jejich `sumCelkem`.
- Faktura vznikne pouze pokud součet `> ABRAFLEXI_MINIMAL_INVOICING` (přísné větší, nikoli `>=`).
- Pokud je součet pod nebo roven limitu, do výsledku se zapíše např. `123.45 < 200` a faktura se nyní nevytvoří.
- Pokud kód zákazníka (podřetězcově) figuruje v `ABRAFLEXI_SKIPLIST`, fakturace se přeskočí bez ohledu na částku.
- `ABRAFLEXI_CREATE_EMPTY_ORDERS` umožní evidenčně vytvořit i nulové objednávky (např. pro přiložení výpisu hovorů a sledování zpracování měsíce).

### Používané proměnné prostředí

- `ABRAFLEXI_MINIMAL_INVOICING` – limit pro fakturaci
- `ABRAFLEXI_PRODUCT` – kód produktu, který musí být v objednávce přítomen
- `ABRAFLEXI_ORDERTYPE` – typ objednávkového dokladu
- `ABRAFLEXI_DOCTYPE` – typ výsledné faktury
- `ABRAFLEXI_SKIPLIST` – seznam kódů zákazníků k přeskočení (vyhledává se podřetězec)
- `ABRAFLEXI_SEND` – zda označit fakturu k odeslání e‑mailem
- `ABRAFLEXI_CREATE_EMPTY_ORDERS` – vytváření prázdných (nulových) objednávek

### Hraniční situace

- Součet přesně rovný limitu → nevystaví se (protože podmínka je `>`)
- Zákazník bez e‑mailu → faktura/výpis se vytvoří, ale e‑mail se nepošle
- Skip list má přednost před částkou
- Není-li žádná připravená objednávka, nic se nefakturuje

### Doporučená možná vylepšení

- Změnit podmínku na `>=` pokud chcete zahrnout přesně dosažený limit
- Místo podřetězcového hledání ve skiplistu použít strukturované parsování (CSV + exact match)
- Přidat testy pro: pod limitem, rovno limitu, nad limitem, ve skiplistu

### Tok zpracování

1. Z IPEX API se vytvoří měsíční (postpaid) objednávky.
2. Najdou se připravené objednávky s daným produktem.
3. Seskupí se podle zákazníka a sečte se `sumCelkem`.
4. Pokud součet > limit → vytvoří se faktura, připojí výpis hovorů, objednávky se označí jako hotové.
5. Jinak objednávky zůstávají připravené pro pozdější navýšení.

---

Pokud potřebujete další části dokumentace v češtině, lze je sem doplnit.
