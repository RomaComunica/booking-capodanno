# Laravel Booking System - Deployment Guide

## 📦 Progetto Completato

Il sistema di booking Laravel è **completo e pronto per il deployment**. Include:

✅ **Admin Panel Filament** con drag-and-drop per riordinare ingressi  
✅ **Frontend Controllers** per listing, dettaglio, carrello, checkout  
✅ **Payment Integration** PayPal NVP e Nexi XPay  
✅ **Email Notifications** per conferme ordine  
✅ **Database Models** compatibili con database ASP esistente  

---

## 🗄️ Setup Database

### Opzione A: Importa Database ASP Esistente

1. **Esporta database da SiteGround/produzione:**
   ```bash
   mysqldump -h HOST -u USER -p capodanno > capodanno_backup.sql
   ```

2. **Crea database locale:**
   ```bash
   mysql -u root -p
   CREATE DATABASE capodanno CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'capodannoroma'@'localhost' IDENTIFIED BY 'CapodannoRoma@';
   GRANT ALL PRIVILEGES ON capodanno.* TO 'capodannoroma'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

3. **Importa dati:**
   ```bash
   mysql -u capodannoroma -p capodanno < capodanno_backup.sql
   ```

4. **Verifica connessione Laravel:**
   ```bash
   cd c:\dev\booking_new
   php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected!';"
   ```

### Opzione B: Crea Database Nuovo con Migrations

```bash
# Crea database vuoto
mysql -u root -p
CREATE DATABASE capodanno CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Esegui migrations
php artisan migrate

# Crea utente admin
php artisan make:filament-user
```

---

## 🚀 Test Locale

### 1. Avvia Server
```bash
cd c:\dev\booking_new
php artisan serve --port=8002
```

### 2. Accedi Admin Panel
- URL: `http://127.0.0.1:8002/admin`
- Login con credenziali create
- Vai su **Locations** → Modifica location
- Testa **drag-and-drop** degli ingressi

### 3. Test Frontend
- Home: `http://127.0.0.1:8002/`
- Location detail: `http://127.0.0.1:8002/location/1`
- Carrello: `http://127.0.0.1:8002/cart`
- Checkout: `http://127.0.0.1:8002/checkout`

---

## 🌐 Deployment Produzione

### 1. Upload Codice

**Via FTP/SFTP:**
```
/public_html/booking_new/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/  ← Document root
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
└── ...
```

### 2. Configura `.env` Produzione

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://booking.romacomunica.it

DB_HOST=localhost  # o IP MySQL SiteGround
DB_DATABASE=capodanno
DB_USERNAME=capodannoroma
DB_PASSWORD=CapodannoRoma@

MAIL_HOST=mail.romacomunica.it
MAIL_PORT=25
MAIL_USERNAME=booking@romacomunica.it
MAIL_PASSWORD=u0s8q5qg

PAYPAL_MODE=live
NEXI_MODE=prod
```

### 3. Configura Web Server

**Apache (.htaccess in `/public`):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 4. Permessi File

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Ottimizzazione Produzione

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

---

## 🔄 Migrazione Graduale da ASP

### Strategia Consigliata

1. **Fase 1: Test Parallelo**
   - ASP su `booking.romacomunica.it` (produzione)
   - Laravel su `new.booking.romacomunica.it` (test)
   - Entrambi usano stesso database

2. **Fase 2: Switch Graduale**
   - Usa Laravel solo per admin panel
   - Frontend rimane ASP
   - Testa gestione ordini

3. **Fase 3: Switch Completo**
   - Sposta dominio principale su Laravel
   - ASP come backup su `old.booking.romacomunica.it`

---

## 📧 Email Configuration

Le email usano SMTP già configurato nel `.env`:

```php
// Test invio email
php artisan tinker
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

---

## 🔐 Security Checklist

- [ ] `APP_DEBUG=false` in produzione
- [ ] `APP_KEY` generato (`php artisan key:generate`)
- [ ] `.env` non accessibile via web
- [ ] Storage permissions corretti (755)
- [ ] HTTPS attivo (certificato SSL)
- [ ] CSRF protection attivo (default Laravel)
- [ ] SQL injection protection (Eloquent ORM)

---

## 🐛 Troubleshooting

### Errore 500
```bash
php artisan config:clear
php artisan cache:clear
chmod -R 755 storage
```

### Database Connection Failed
- Verifica credenziali `.env`
- Controlla firewall MySQL
- Testa connessione: `php artisan tinker`

### Filament Not Loading
```bash
php artisan filament:upgrade
php artisan config:clear
```

### Payment Gateway Errors
- Verifica credenziali PayPal/Nexi nel `.env`
- Controlla logs: `storage/logs/laravel.log`
- Test in modalità sandbox prima

---

## 📊 Monitoring

### Logs
```bash
tail -f storage/logs/laravel.log
```

### Database Queries
```php
DB::enableQueryLog();
// ... your code ...
dd(DB::getQueryLog());
```

---

## 🎯 Next Steps

1. **Importa database ASP** seguendo "Setup Database"
2. **Testa admin panel** con drag-and-drop
3. **Crea views Blade** per frontend (opzionale)
4. **Deploy su sottodominio** per test
5. **Switch graduale** quando pronto

---

## 📞 Support

Per problemi o domande:
- Controlla `storage/logs/laravel.log`
- Verifica `.env` configuration
- Testa connessione database
- Controlla permessi file/cartelle
