# smartassistant.sk – práca s webom cez Clauda

Odpovedaj po slovensky. Application Password nikdy nevypisuj do odpovedí ani neukladaj do repa.

## Cieľ

Cez Clauda meniť články a upravovať vzhľad webu **smartassistant.sk** (SMART HOME, SoftArt, s.r.o.).
Spôsob: **WordPress REST API + Application Password**. Remote Control z Macu sa nepoužíva (Mac nemá musieť bežať),
pracuje sa cez **Claude Code na webe (claude.ai/code)** s týmto repom.

## Web

- WordPress 6.9+, zdieľaný hosting (nginx), téma **Astra**
- **Elementor + Elementor Pro**, Ultimate Addons / Header Footer Elementor (`hfe/v1`), Rank Math, CookieYes
- Application Passwords sú zapnuté
- Jediný používateľ: ID 1, meno „SMART HOME“, verejný slug `veronika` (login neoverený)

| ID | Typ | Názov | Builder |
|---|---|---|---|
| 3307 | článok | SMART HOME 2025: Matter, lokálne ovládanie a AI asistenti | Elementor |
| 3304 | stránka | Blog Smart Home | Gutenberg/klasický |
| 3203 | stránka | Súbory COOKIES | Elementor |
| 1217 | stránka | Inteligentná domácnosť | Elementor |
| 1216 | stránka | Realizácie inteligentných domácností | Elementor |
| 1215 | stránka | SMART HOME (úvodná) | Elementor |
| 16 | stránka | Skúsenosti so SMART HOME | Elementor |
| 15 | stránka | Kontakt | Elementor |
| 3 | stránka | Ochrana osobných údajov | Elementor |
| 1902 | Elementor šablóna | Pätička | Elementor |

Obsah Elementor stránok je v post meta `_elementor_data` (JSON), nie v poli `content`.
Bežné `wp/v2` REST ho nevracia ani nemení, preto existuje vlastný endpoint `claude/v1/elementor/<id>`
(snippet v `wordpress/claude-elementor-endpoint.php`, inštaluje sa cez plugin Code Snippets).

## Prihlásenie

V claude.ai/code je heslo nastavené ako **Network secret** pre `smartassistant.sk`
(hlavička `Authorization: Basic <base64 login:heslo>`). Proxy ju pridá sama, preto v príkazoch **nepoužívaj `-u`**.

```bash
# overenie prihlásenia
curl -s 'https://smartassistant.sk/wp-json/wp/v2/users/me?context=edit'

# článok vrátane surového obsahu
curl -s 'https://smartassistant.sk/wp-json/wp/v2/posts/3307?context=edit'

# Elementor layout (záloha pred zmenou)
curl -s 'https://smartassistant.sk/wp-json/claude/v1/elementor/1215' -o backups/1215-$(date +%Y%m%d-%H%M).json

# zápis upraveného layoutu (payload.json = {"data": "<Elementor JSON ako string>"})
curl -s -X POST -H 'Content-Type: application/json' \
  --data @payload.json 'https://smartassistant.sk/wp-json/claude/v1/elementor/1215'

# globálne farby a písma Elementora
curl -s 'https://smartassistant.sk/wp-json/elementor/v1/globals'
```

Ďalšie užitočné endpointy: `wp/v2/pages`, `wp/v2/media`, `elementor/v1/cache` (vyčistenie CSS cache),
`hfe/v1/mcp-abilities` (nepreskúmané).

## Pravidlá

- **Pred každou zmenou Elementor stránky ulož zálohu JSON** do `backups/` a commitni ju. Server drží len jednu predošlú verziu.
- Najprv ukáž, čo sa zmení, a počkaj na súhlas; potom zapisuj.
- Po zmene over výsledok na živom webe.

## Stav

**Čaká na majiteľa**
- [ ] Vymeniť Application Password (starý bol v chate)
- [ ] Nainštalovať Code Snippets a vložiť snippet
- [ ] Nastaviť prostredie v claude.ai/code (Network access → Custom + `smartassistant.sk`, Network secret)
- [ ] Zistiť WP login (možno `veronika`)

**Potom Claude**
- [ ] Test prihlásenia (`/wp/v2/users/me`)
- [ ] Test čítania Elementor dát (GET `claude/v1/elementor/<id>`)
- [ ] Prvá skúšobná zmena so zálohou

## Otvorené otázky

- Vlastný admin účet alebo samostatný používateľ „claude“ (kvôli auditu v revíziách)?
- Nové články v Gutenbergu (jednoduché cez REST) alebo v Elementore (zhodný dizajn)?
- Má hosting page cache, ktorú treba po zmene čistiť?
- Čo presne ponúka `hfe/v1/mcp-abilities`?
