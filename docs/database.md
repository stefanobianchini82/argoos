# Database

## Partizionamento MySQL (PARTITION BY RANGE)

Una **partition** è un modo per dividere fisicamente una tabella grande in pezzi più piccoli, mantenendo però un'unica interfaccia logica. Dal punto di vista del codice applicativo la tabella è una sola; MySQL internamente la gestisce come tanti segmenti separati.

In Argoos usiamo `PARTITION BY RANGE` basato su `UNIX_TIMESTAMP(collected_at)`: ogni partizione contiene le righe il cui `collected_at` cade in un certo intervallo di tempo (tipicamente un mese). Quando arrivano nuovi dati vanno nella partizione giusta in automatico.

### Perché usarlo

- **Cancellazione rapida dei dati storici.** Eliminare una partizione (`ALTER TABLE … DROP PARTITION p_2024_01`) è un'operazione istantanea — equivale a cancellare un file — mentre un `DELETE` su milioni di righe è lento e produce frammentazione.
- **Query più veloci.** MySQL legge solo le partizioni che contengono le righe cercate (*partition pruning*). Una query su `collected_at` dell'ultima settimana non tocca i dati del mese scorso.
- **Manutenzione più semplice.** Si possono ottimizzare o ricostruire singole partizioni senza bloccare l'intera tabella.

### Vincolo sulla PRIMARY KEY

MySQL impone che **tutte le colonne usate nella funzione di partizionamento siano parte della PRIMARY KEY**. Per questo le tabelle `metrics` e `disk_partitions` hanno una chiave primaria composta `(id, collected_at)` anziché il solo `id`.

### Partizione iniziale

Al momento della creazione esiste una sola partizione `p_initial` con `VALUES LESS THAN MAXVALUE`, che raccoglie tutti i dati. Un job periodico (da implementare) si occuperà di creare le partizioni mensili e di eliminare quelle più vecchie di una soglia configurabile.
