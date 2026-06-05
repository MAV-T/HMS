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
