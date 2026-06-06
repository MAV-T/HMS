# Fix delle vulnerabilità SQL Injection (CWE-89)

## Vulnerabilità identificata

Il codice presentava una vulnerabilità di **SQL Injection** (CWE-89: *Improper
Neutralization of Special Elements used in an SQL Command*), segnalata dallo
strumento di analisi statica Fortify.

La causa principale era la **costruzione dinamica delle query SQL tramite
concatenazione diretta di input proveniente dall'utente** (`$_POST`, `$_SESSION`,
ecc.) all'interno della stringa SQL. In questo modo un valore destinato a essere
interpretato come semplice *dato* poteva invece essere interpretato dal database
come parte del *comando*, permettendo a un attaccante di alterare la logica della
query (es. bypass dell'autenticazione, lettura, modifica o cancellazione non
autorizzata di dati).

## Soluzione adottata: prepared statement con bind parameter

La mitigazione applicata è quella raccomandata da OWASP e dallo stesso Fortify
come difesa primaria contro il SQL injection: l'uso di **prepared statement
parametrizzati** tramite l'estensione `mysqli`.

Il principio è separare in modo strutturale il **comando SQL** dai **dati**:

1. La query viene scritta con dei segnaposto (`?`) al posto dei valori variabili.
2. La query viene inviata al database e "compilata" *prima* che i dati vengano
   forniti (`mysqli_prepare`).
3. I valori dell'utente vengono associati ai segnaposto separatamente
   (`mysqli_stmt_bind_param`), specificando il tipo di ciascuno.
4. La query viene eseguita (`mysqli_stmt_execute`).

Poiché i dati arrivano al database in un momento distinto dalla definizione del
comando, non possono più modificarne la struttura, qualunque carattere
contengano. La protezione è strutturale e non dipende da escaping o validazione
manuale.

## Cosa è stato modificato

In tutti i file interessati, ogni query che includeva input utente è stata
convertita seguendo lo stesso schema:

- I valori concatenati direttamente nella stringa SQL sono stati sostituiti con
  segnaposto `?`.
- Le chiamate dirette a `mysqli_query()` su query con input variabile sono state
  sostituite dal ciclo prepare → bind → execute.
- Per le query di tipo `SELECT` da cui vengono letti i risultati, è stato
  aggiunto il recupero del result set con `mysqli_stmt_get_result()`, in modo che
  le funzioni di lettura esistenti (`mysqli_num_rows`, `mysqli_fetch_array`,
  ecc.) continuassero a funzionare senza ulteriori modifiche.
- I parametri sono stati associati rispettando l'ordine in cui i segnaposto
  compaiono nella query e indicando il tipo corretto per ciascuno.

Sono stati parametrizzati anche i valori provenienti dalla sessione
(`$_SESSION`), non solo quelli da `$_POST`, perché tali valori possono a loro
volta derivare da input utente raccolto in fasi precedenti (es. al login).

## Cosa NON è stato modificato (e perché)

Le query **statiche**, prive di input utente (es. `SELECT * FROM doctb` per
popolare un elenco), **non sono vulnerabili** al SQL injection: la stringa SQL è
definita interamente nel codice lato server, che l'utente non può modificare.
Eventuali manipolazioni della pagina nel browser non hanno effetto sul comando
eseguito dal server. Queste query sono state lasciate invariate. Se lo strumento
di analisi le segnala comunque, si tratta di falsi positivi.

## Note su vulnerabilità correlate (fuori dallo scope di questo fix)

Durante l'intervento sono emerse altre criticità, distinte dal SQL injection e
da affrontare separatamente:

- **Password in chiaro**: le password vengono salvate e confrontate in chiaro nel
  database. La pratica corretta è memorizzare un hash con `password_hash()` e
  verificarlo con `password_verify()`.
- **Possibile Cross-Site Scripting (XSS)**: alcuni valori provenienti dal
  database vengono stampati direttamente nell'HTML senza escaping. La difesa
  consiste nell'avvolgere tali valori con `htmlspecialchars()` prima dell'output.

Queste migliorie non rientrano nel fix del CWE-89 qui documentato, ma sono
consigliate per irrobustire complessivamente l'applicazione.

---

# Fix delle vulnerabilità CSRF (CWE-352) — Batch P3

## Vulnerabilità identificata

**Cross-Site Request Forgery (CSRF)** — CWE-352: *Cross-Site Request Forgery*

- **Finding Fortify:** 38 occorrenze su tutti i pannelli principali
- **OWASP Top 10 2021:** A01 – Broken Access Control
- **OpenCRE:** https://www.opencre.org/cre/340-310

La causa radice era l'assenza di qualsiasi meccanismo di verifica dell'origine
delle richieste POST. Un sito malevolo poteva costruire un form che, al caricamento
da parte di un utente autenticato, eseguiva azioni privilegiate (prenotazione
appuntamento, aggiunta/rimozione dottori) senza il consenso dell'utente.

## Pattern adottato: Synchronizer Token Pattern

Il server genera un token crittograficamente sicuro (`random_bytes(32)`) e lo
memorizza in sessione. Ogni form POST include il token come campo hidden. Al submit
il server confronta il token POST con quello di sessione tramite `hash_equals()`
(confronto a tempo costante — previene timing attack). In caso di mismatch la
richiesta viene rifiutata con HTTP 403.

## Soluzione implementata

File **nuovo** `csrf_helper.php` con tre funzioni centralizzate:

| Funzione              | Scopo                                                         |
|-----------------------|---------------------------------------------------------------|
| `generate_csrf_token()` | Genera/recupera il token dalla sessione (`random_bytes(32)`) |
| `csrf_token_field()`   | Ritorna `<input type="hidden" name="csrf_token" value="...">` |
| `verify_csrf_token()`  | Verifica il token; HTTP 403 in caso di mismatch              |

## File modificati

| File | Intervento | Form / Handler coperti |
|---|---|---|
| `csrf_helper.php` | **NUOVO** | Helper centralizzato (genera, valida token) |
| `index.php` | Token nei form | Patient Register → func2.php; Doctor Login → func1.php; Admin Login → func3.php |
| `index1.php` | Token nel form | Patient Login → func.php |
| `func.php` | Verifica + token | Handler: patsub, update_data, doc_sub; Form: search, payment-update, add-doctor |
| `func1.php` | Verifica + token | Handler: docsub1; Form: search, payment-update, add-doctor |
| `func2.php` | Verifica + token | Handler: patsub1, update_data, doc_sub; Form: search, payment-update, add-doctor |
| `func3.php` | Verifica | Handler: adsub, update_data, doc_sub |
| `admin-panel.php` | Verifica + token | Handler: app-submit; Form: appointment booking, add-doctor |
| `admin-panel1.php` | Verifica + token | Handler: docsub, docsub1; Form: doctorsearch, patientsearch, appsearch, add-doctor, delete-doctor, messearch |
| `doctor-panel.php` | Token nei form | Form: search, add-doctor |
| `prescribe.php` | Verifica + token | Handler: prescribe; Form: prescription form |

## Finding risolti

**FIX-04** — 38 finding CSRF (CWE-352) — **RISOLTI**

## Note di sicurezza

- `hash_equals()` previene timing attack nel confronto del token
- `htmlspecialchars()` applicato al token in output (prevenzione XSS nel campo hidden)
- Business logic invariata in tutti i file
