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
    discovery.php        robots.txt, schema NewsArticle, llms.txt
    preferred-source.php fonte preferita su Google
  template-parts/        card degli articoli e ticker
  assets/js/app.js       menu, ricerca, ticker, navbar agganciata
bin/
  fetch-fonts.php        scarica Roboto in locale
  convert-images.php     genera WebP e AVIF
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

**2. Dati strutturati.** Lo schema di Yoast passa da `Article` a `NewsArticle` e
viene completato con `articleSection`, `wordCount`, `inLanguage`,
`isAccessibleForFree` e `speakable`. Se Yoast venisse disattivato, il tema
emette uno schema di riserva completo.

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
| `gpmi_remove_global_styles` | `false` | vedi sotto |
| `gpmi_remove_block_library` | `false` | rimuove il CSS dei blocchi |
| `gpmi_show_preferred_source` | `true` | invito "fonte preferita su Google" |

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
