# Booking Legacy — Laravel Skeleton (Compatibilità 1:1)

Questo pacchetto contiene **model, controller, servizi e viste** già mappati sul tuo **schema MySQL esistente**
(senza rinominare tabelle o colonne), così il nuovo sistema in **Laravel 11 + PHP 8.2/8.3** potrà leggere/scrivere
direttamente sul DB legacy **senza perdere dati**.

## Come usare
1. Crea un nuovo progetto Laravel 11 (o usa un progetto esistente):
   ```bash
   composer create-project laravel/laravel booking-app
   cd booking-app
   ```

2. Copia il contenuto di questa cartella **dentro** il tuo progetto Laravel, rispettando le directory.

3. Installa i pacchetti consigliati:
   ```bash
   composer require barryvdh/laravel-dompdf:^2.0 bacon/bacon-qr-code:^2.0
   composer require srmklive/paypal:^4.0
   composer require spatie/laravel-permission:^6.0
   composer require maatwebsite/excel:^3.1
   ```

4. Configura la connessione DB in `.env` (mantieni lo **schema esistente**):
   - Forza charset/collezione a `utf8mb4`
   - Imposta `DB_*` con le credenziali del DB legacy
   - Disattiva la modalità strict per tollerare `0000-00-00` se presenti

   **Esempio** di override in `config/database.php`:
   ```php
   'mysql' => [
       'driver' => 'mysql',
       'host' => env('DB_HOST'),
       'port' => env('DB_PORT', '3306'),
       'database' => env('DB_DATABASE'),
       'username' => env('DB_USERNAME'),
       'password' => env('DB_PASSWORD'),
       'charset' => 'utf8mb4',
       'collation' => 'utf8mb4_general_ci',
       'prefix' => '',
       'strict' => false, // IMPORTANTE per compatibilità legacy
       'engine' => null,
   ],
   ```

5. Configura le **email** in `.env` usando **la stessa identica casella** che usavi prima (SMTP SiteGround o altro):
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.siteground.net
   MAIL_PORT=465
   MAIL_USERNAME=LA_TUA_USERNAME
   MAIL_PASSWORD=LA_TUA_PASSWORD
   MAIL_ENCRYPTION=ssl
   MAIL_FROM_ADDRESS=la_tua_email@tuodominio.it
   MAIL_FROM_NAME="Booking"
   ```

6. Registra le **routes** (questo pacchetto fornisce `routes/web.php` completo). Se hai già un file `routes/web.php`,
   **unisci** i contenuti.

7. Esegui l'app in **staging** puntando a un **clone del DB**, poi al Go-Live cambia solo la connessione `.env`.

## Flussi inclusi
- **Booking**: selezione ingressi → carrello → ordine (pending)
- **Pagamento PayPal** (stub pronto, con webhook)
- **Conferma ordine** + **invio email** con **allegato voucher PDF** (service + mailable)
- **Voucher PDF + QR** (template Blade + service)
- **Model Eloquent** mappati sullo schema esistente (es. join su `numeroOrdine`)

## Note
- Le classi sono **stub pronte** da completare con la logica specifica (prezzi, disponibilità, capienza, ecc.).
- Non abbiamo modificato **nomi tabella** o **colonne**: i Model fanno riferimento diretto al legacy.
- Se alcune password utenti non sono hashate, implementa un meccanismo di **rehash on login**.

Buon lavoro! 🚀
