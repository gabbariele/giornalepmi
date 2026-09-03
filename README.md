# Il Giornale delle PMI — tema custom

Tema WordPress che sostituisce Newsmatic mantenendone l'aspetto, senza il peso.
Nessun framework, nessun page builder, nessuna dipendenza da jQuery: un foglio
di stile, uno script, e il resto lo fa PHP.

## Cosa cambia, in numeri

Misure prese sulla homepage, prima sul sito in produzione e poi sul tema nuovo
con contenuti equivalenti.

| | Newsmatic | Tema nuovo |
|---|---|---|
| CSS | 295 KB su file + 47 KB inline | **27 KB** (6 KB gzip) su file |
| JavaScript | jQuery + migrate + slick + marquee + jquery-cookie + emoji ≈ 63 KB | **8 KB** (2 KB gzip), zero dipendenze |
| Librerie di icone | FontAwesome 5 **e** 6, entrambe complete | SVG inline, poche centinaia di byte |
| Famiglie di font | Roboto, Inter, Jost da Google Fonts | Roboto variabile, **1 file da 42 KB**, servito dal tuo dominio |
| Immagini homepage | 2.367 KB | ~450 KB, e **−76%** dopo la conversione in WebP/AVIF |
| Richieste | 66 | ~15 |
| HTML | — | 10 KB gzip |

Il grosso del guadagno non è il tema: sono le immagini. `AI-Act.png` da 673 KB e
i banner di sidebar da 417 e 406 KB sono PNG a piena risoluzione serviti senza
ridimensionamento. Lo script di conversione risolve quello.

## Installazione

```bash
git clone https://github.com/gabbariele/giornalepmi.git
cp -r giornalepmi/wp-content/themes/gpmi /percorso/wp-content/themes/
```

Poi, **prima di attivare**, dal Customizer di Newsmatic annota logo, menu e
widget: WordPress conserva i menu e i widget fra i temi, ma le impostazioni
specifiche di Newsmatic (colori, layout, codice nel `<head>`) no.

Attiva il tema da **Aspetto → Temi**, poi:

```bash
php bin/fetch-fonts.php                    # scarica Roboto in locale (42 KB)
php bin/convert-images.php --apply --avif  # genera WebP e AVIF della media library
```

Le miniature vanno rigenerate una volta, perché il tema registra formati propri
tagliati sulle dimensioni reali del layout (con WP-CLI: `wp media regenerate --yes`).

## Da fare a mano dopo l'attivazione

Queste cose vivevano nel tema o nei plugin precedenti e vanno ricollocate.

1. **Codice nel `<head>`.** I meta tag `ai-*` e lo script dei form Weevo erano
   iniettati da un campo "codice personalizzato". I meta `ai-*` sono ora nel
   tema (`inc/discovery.php`); **lo script Weevo va rimesso**, tramite GTM o un
   plugin di header/footer.
2. **Plugin "Add as preferred source": si può disattivare.** La funzione è
   integrata nel tema (`inc/preferred-source.php`) e punta allo stesso URL,
   `https://www.google.com/preferences/source?q=<dominio>`.
3. **Menu**: assegnali in *Aspetto → Menu* alle posizioni `primary`, `topbar`,
   `footer`, `social`.
4. **Widget**: la sidebar si chiama ora *Sidebar principale*, il footer ha
   quattro colonne.
5. **Logo**: *Aspetto → Personalizza → Identità del sito*.
6. **Dati legali** in *Personalizza → Dati legali della testata*: editore, sede
   e direttore responsabile. Compaiono nel footer e nei dati strutturati come
   `NewsMediaOrganization` ed `editor`.
7. **Riquadro autore**: se un plugin ne aggiunge già uno, spegni quello del
   tema in *Personalizza → Homepage* per non vederli doppi.

## Come e' composta la homepage

Due blocchi, senza sovrapposizioni:

1. **Apertura** (1 grande + 4 medi): gli ultimi cinque articoli pubblicati.
2. **Griglia** (3 colonne): prima gli articoli in evidenza, poi gli altri per
   data. Gli articoli dell'apertura sono esclusi da **tutte** le pagine, non
   solo dalla prima: escluderli solo in testa sfaserebbe la paginazione e li
   farebbe ricomparire piu' avanti.

Un articolo in evidenza che sia gia' fra gli ultimi cinque resta solo in
apertura, senza comparire due volte.

Il numero di articoli della griglia viene arrotondato per eccesso al multiplo
delle colonne: con il valore predefinito di WordPress (10 articoli per pagina)
e tre colonne diventa 12, cosi' l'ultima riga non resta con due celle vuote.
Si regola da *Impostazioni - Lettura* e da *Personalizza - Homepage*.

## Struttura

```
wp-content/themes/gpmi/
  functions.php          carica i moduli
  theme.json             preset dei blocchi, larghezze del contenuto
  style.css              tutto il CSS del sito
  inc/
    setup.php            supporti del tema, menu, sidebar, formati immagine
    assets.php           CSS, JS e font (una richiesta ciascuno)
    performance.php      rimozioni, header di cache, speculation rules
    images.php           priorità LCP, <picture> AVIF/WebP, attributi sizes
    queries.php          query di homepage con cache invalidata alla pubblicazione
    template-tags.php    icone SVG, metadati, breadcrumb, paginazione
    nav-walker.php       menu con sottomenu accessibili
    customizer.php       colori, ticker, opzioni di homepage
    discovery.php        robots.txt per i crawler IA, llms.txt
    seo.php              meta, Open Graph, canonical, grafo JSON-LD
    comments.php         chiusura dei commenti a livello di sito
    preferred-source.php fonte preferita su Google
  template-parts/        card degli articoli e ticker
  assets/js/app.js       menu, ricerca, ticker, navbar agganciata
bin/
  fetch-fonts.php        scarica Roboto in locale
  convert-images.php     genera WebP e AVIF
  write-robots.php       scrive robots.txt su disco (necessario su WordOps)
```

## Indicizzazione e risposte generative

Tre livelli, in ordine di efficacia reale.

**1. `robots.txt` — è qui che si decide.** Il tema aggiunge blocchi espliciti per
i crawler documentati: GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot,
Claude-User, Claude-SearchBot, Google-Extended, PerplexityBot, Perplexity-User,
Applebot-Extended, meta-externalagent, CCBot, Amazonbot, DuckAssistBot,
cohere-ai, YouBot, Bytespider. Tutti consentiti per impostazione predefinita.
Per revocare il consenso a uno di loro:

```php
add_filter( 'gpmi_ai_crawlers', function ( $bots ) {
	$bots['Bytespider'] = false; // diventa Disallow: /
	return $bots;
} );
```

**2. Dati strutturati e SEO.** Il tema non dipende da nessun plugin SEO:
`inc/seo.php` genera meta description, Open Graph, Twitter Card, canonical
(anche sugli archivi paginati, dove il core non lo mette) e un grafo JSON-LD
completo — `NewsMediaOrganization`, `WebSite` con `SearchAction`, `WebPage`,
`BreadcrumbList` e `NewsArticle` con autore, sezioni, tag, immagine,
`wordCount`, `speakable` e direttore responsabile.

Le descrizioni scritte a mano in Yoast restano nel database anche dopo la
disinstallazione: il tema le riusa come prima sorgente (`_yoast_wpseo_metadesc`,
`_yoast_wpseo_title`, `_yoast_wpseo_primary_category`), così anni di lavoro
editoriale non vanno persi.

Se un plugin SEO viene installato (Yoast, Rank Math, AIOSEO, SEOPress) il
modulo si spegne da solo per non duplicare nulla; con Yoast attivo il tema si
limita a portarne lo schema da `Article` a `NewsArticle`.

**3. `ai-*` e `llms.txt`.** Da sapere: **i meta tag `ai-train`, `ai-access`,
`ai-summary`, `ai-metadata`, `ai-purpose`, `ai-distribution` non sono uno
standard e nessun crawler noto li interpreta.** Sono conservati perché erano già
sul sito e perché dichiarano in chiaro la posizione dell'editore, non perché
producano un effetto misurabile. Stesso discorso per `/llms.txt`, generato dal
tema e aggiornato a ogni pubblicazione: è una convenzione emergente, non ancora
consumata dai crawler principali. Il consenso effettivo passa dal `robots.txt`.

## Filtri disponibili

| Filtro | Default | Effetto |
|---|---|---|
| `gpmi_ai_crawlers` | tutti `true` | consenso per singolo crawler |
| `gpmi_ai_meta_tags` | 6 tag | i meta `ai-*` |
| `gpmi_enable_speculation_rules` | `true` | prerender del link sotto il cursore |
| `gpmi_send_cache_headers` | `true` | header `Cache-Control` |
| `gpmi_use_picture_element` | `true` | `<picture>` con AVIF/WebP |
| `gpmi_query_cache_ttl` | 10 min | durata cache delle query di homepage |
| `gpmi_fallback_menu_count` | 8 | categorie mostrate se manca il menu |
| `gpmi_remove_global_styles` | `false` | vedi sotto |
| `gpmi_remove_block_library` | `false` | rimuove il CSS dei blocchi |
| `gpmi_show_preferred_source` | `true` | invito "fonte preferita su Google" |
| `gpmi_seo_enabled` | `true` se non c'è un plugin SEO | meta, canonical e JSON-LD del tema |

### I 12 KB di `global-styles`

WordPress genera da solo circa 12 KB di CSS inline (`global-styles`) con i
preset dei blocchi. Non è colpa del tema — Newsmatic li aveva dentro i suoi
47 KB inline — e il `theme.json` del tema li limita già a quel che serve.
Si possono togliere del tutto:

```php
add_filter( 'gpmi_remove_global_styles', '__return_true' );
```

Non è attivo di default perché l'archivio ha 11.500 articoli: se anche solo
alcuni usano blocchi con colori preimpostati, quei colori sparirebbero. Vale la
pena provarlo su un paio di articoli vecchi prima di attivarlo ovunque.

## Personalizzazioni CSS utili

L'altezza minima degli iframe nei widget (modulo newsletter) si regola con una
variabile, senza toccare il foglio di stile — da *Aspetto → Personalizza → CSS
aggiuntivo*:

```css
:root { --gp-widget-iframe-height: 560px; }
```

## robots.txt su WordOps

WordOps intercetta `/robots.txt` con un blocco nginx in
`/etc/nginx/common/wpcommon-phpXX.conf`:

```nginx
location = /robots.txt {
    try_files $uri $uri/ /index.php?$args @robots;
}
location @robots {
    return 200 "User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
";
}
```

Se non esiste un file vero, nginx serve quel fallback hardcoded e WordPress non
viene mai chiamato: nessuna direttiva del tema raggiunge i crawler. Il primo
parametro di `try_files` e' pero' `$uri`, quindi basta che il file esista.

```bash
php bin/write-robots.php --apply
```

Scrive in `htdocs/robots.txt` esattamente cio' che WordPress genererebbe, filtri
del tema compresi. Va rilanciato dopo ogni modifica a `gpmi_ai_crawlers`.

Quel file di configurazione e' condiviso da tutti i siti del server e viene
sovrascritto dagli aggiornamenti di WordOps: meglio non modificarlo.

La CDN tiene `robots.txt` in cache per ore (`max-age=14400` su Cloudflare):
dopo la scrittura va purgato quell'URL, altrimenti si continua a vedere la
versione vecchia.

## Note operative

- **Prerender.** Le Speculation Rules precaricano il link sotto il cursore. Dopo
  un deploy, una pagina già prerenderizzata può restare vecchia per qualche
  secondo nel browser di chi stava navigando. È il comportamento previsto e
  rispetta gli header di cache.
- **Cache a pagina intera.** L'orologio nella barra superiore è scritto dal
  browser, non dal server: l'HTML resta identico per tutti e la cache FastCGI
  funziona. Gli header inviati dal tema (`max-age`, `stale-while-revalidate`)
  sono compatibili con WordOps e Cloudflare.
- **Font mancanti.** Se `assets/fonts/` è vuota, il tema non emette nessuna
  `@font-face` e usa lo stack di sistema. Nessuna richiesta sprecata.
- **Descrizioni delle voci di menu.** Non finiscono più dentro il link: sul tema
  precedente il nome accessibile della voce era `INFOIMPRESAInformazioni sulle
  Imprese in Italia, Finanziamenti per le Imprese`.

## Ambiente di prova

Il tema è stato sviluppato e verificato su WordPress 7.1 con SQLite, PHP 8.3.
Tutti i template rispondono senza errori PHP: homepage, articolo, categoria,
autore, ricerca, paginazione, 404.
