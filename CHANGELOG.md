# Diario delle versioni

Il numero di versione sta in due punti che vanno tenuti allineati:
`style.css` (intestazione `Version:`, quella che legge WordPress) e la costante
`GPMI_VERSION` in `functions.php`.

Si usa [versionamento semantico](https://semver.org/lang/it/): il primo numero
cambia se qualcosa si rompe e richiede un intervento manuale, il secondo se si
aggiungono funzioni, il terzo per le sole correzioni.

Ogni versione ha un tag git corrispondente, quindi si può sempre tornare
indietro:

```bash
git checkout v1.0.0 -- wp-content/themes/gpmi
```

---

## 1.1.0

Prima serie di interventi dopo la messa in produzione.

### Aggiunto

- **Foto degli autori caricabili dal sito.** WordPress da solo si appoggia a
  Gravatar, che mostra l'immagine solo a chi ha un account collegato a quella
  email: su oltre cento firme significa quasi nessuna foto. Ora la si sceglie
  dalla libreria media nel profilo utente e vale ovunque — riquadro autore,
  commenti, elenco utenti, dati strutturati. Le foto caricate in passato con un
  plugin poi disattivato vengono ritrovate da sole nel database.
- **Modulo SEO completo** (`inc/seo.php`): meta description, Open Graph,
  Twitter Card, canonical anche sugli archivi paginati e grafo JSON-LD con
  `NewsMediaOrganization`, `WebSite`, `WebPage`, `BreadcrumbList` e
  `NewsArticle`. Il tema non dipende più da un plugin SEO; se ne viene
  installato uno, il modulo si spegne da solo.
- **Chiusura dei commenti** a livello di sito, con interruttore separato per
  nascondere anche quelli già pubblicati.
- **Dati legali della testata** (editore, sede, direttore responsabile) nel
  footer e nei dati strutturati.
- **`bin/write-robots.php`**, necessario su WordOps, dove nginx serve un
  robots.txt hardcoded senza mai interpellare WordPress.
- Fonte preferita su Google integrata nel tema, al posto del plugin.

### Corretto

- **Uscita dal blocco JSON-LD.** Un titolo o un nome di categoria contenente
  `</script>` avrebbe chiuso il blocco in anticipo, facendo interpretare come
  HTML tutto ciò che seguiva. Aggiunto `JSON_HEX_TAG`.
- **Contenuti protetti da password in cache condivisa.** Ricevevano header
  `Cache-Control: public`: una volta inserita la password un proxy avrebbe
  potuto conservare il testo in chiaro e servirlo a chiunque.
- **Articoli in evidenza ignorati** dalla homepage, e articoli dell'apertura che
  ricomparivano nelle pagine successive sfasando la paginazione.
- **Griglia con celle vuote** in fondo: il numero di articoli ora è arrotondato
  al multiplo delle colonne.
- **Tag HTML visibili** nelle didascalie delle immagini, nella biografia
  dell'autore e negli estratti: erano stampati con `esc_html` invece che
  filtrati con `wp_kses_post`.
- **Menu di navigazione vuoto** dopo il cambio tema: ora ripiega sul menu del
  footer e poi sulle categorie.
- **Sitemap inesistente dichiarata** nel robots.txt quando la generazione di
  Yoast era disattivata.
- **Nomi dei mesi maiuscoli** nella barra superiore: in italiano vanno
  minuscoli, era il CSS a forzarli.
- Roboto veniva scaricato due volte: è un font variabile, un solo file copre
  tutti i pesi.

---

## 1.0.0

Primo rilascio. Sostituisce il tema Newsmatic mantenendone l'aspetto.

- CSS da 295 KB più 47 KB inline a 27 KB; JavaScript da circa 63 KB fra jQuery,
  slick, marquee e jquery-cookie a 8 KB senza dipendenze; due librerie
  FontAwesome sostituite da icone SVG inline.
- Immagini con formati tagliati sul layout, priorità esplicita sull'immagine
  LCP e `<picture>` con AVIF e WebP.
- Query di homepage in cache, invalidate alla pubblicazione.
- `robots.txt` con i crawler di IA documentati, `llms.txt`, schema
  `NewsArticle`.
