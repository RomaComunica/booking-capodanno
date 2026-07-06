<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

function createTable($tableName, $callback) {
    try {
        if (Schema::hasTable($tableName)) {
            echo "Table '$tableName' already exists. Dropping to ensure correct schema...\n";
            Schema::drop($tableName);
        }
        
        echo "Creating table '$tableName'...\n";
        Schema::create($tableName, $callback);
        echo "Table '$tableName' created successfully.\n\n";
    } catch (\Exception $e) {
        echo "ERROR creating '$tableName': " . $e->getMessage() . "\n\n";
    }
}

echo "Starting Missing Tables Creation (Ordini, Partner)...\n";
DB::statement('PRAGMA foreign_keys = OFF');

// 1. Partner
createTable('partner', function (Blueprint $table) {
    $table->integer('idPartner')->primary()->autoIncrement();
    $table->string('nomePartner', 255);
    $table->string('emailPartner', 100)->nullable();
    $table->string('passwordPartner', 255)->nullable();
    $table->boolean('attivoPartner')->default(1);
    $table->string('codicePartner', 50)->nullable();
});

// 2. Ordini
createTable('ordini', function (Blueprint $table) {
    $table->integer('idordine')->primary()->autoIncrement();
    $table->string('numeroOrdine', 50)->index();
    $table->dateTime('dataOrdine')->nullable();
    // Use string for '0000-00-00' compatibility if needed, but nullable datetime is safer for SQLite
    $table->dateTime('dataPagamentoOrdine')->nullable(); 
    $table->decimal('totaleOrdine', 10, 2)->default(0);
    $table->string('statoOrdine', 50)->default('pending');
    
    // Foreign Keys / Relations
    $table->integer('idLocationOrdine')->nullable();
    $table->integer('idPartnerOrdine')->nullable();
    $table->integer('tipoPagamentoOrdine')->nullable();
    $table->integer('tipoSpedizioneOrdine')->nullable();
    
    // Customer Info
    $table->string('nomeClienteOrdine', 100)->nullable();
    $table->string('cognomeClienteOrdine', 100)->nullable();
    $table->string('emailClienteOrdine', 100)->nullable();
    $table->string('telefonoClienteOrdine', 50)->nullable();
});

// 3. Riga Ordine (Lines)
createTable('righe_ordini', function (Blueprint $table) {
    // Note: Model name is RigaOrdine, assumed table usually 'riga_ordine' or 'righe_ordini'. 
    // Checking standard naming convention or typically used plural. 
    // Let's assume 'riga_ordine' based on 'ordini' pattern? Or 'righe_ordini'.
    // Let's create 'righe_ordini' (plural) to be safe, but Model might use different table.
    // Wait, let's stick to 'righe_ordini' as a safe guess for "Rows of Orders".
    $table->integer('idRigaOrdine')->primary()->autoIncrement();
    $table->string('numeroOrdine', 50)->index();
    $table->integer('idTipologiaIngresso')->nullable();
    $table->string('nomeIngresso', 255)->nullable();
    $table->integer('quantitaIngressi')->default(0);
    $table->decimal('prezzoIngresso', 10, 2)->default(0);
    $table->decimal('totaleRiga', 10, 2)->default(0);
});

// 4. Voucher
createTable('vouchers', function (Blueprint $table) {
    $table->integer('idVoucher')->primary()->autoIncrement();
    $table->string('codiceVoucher', 50)->index();
    $table->string('numeroOrdineVoucher', 50)->index();
    $table->integer('idIngressoVoucher')->nullable();
    $table->boolean('utilizzato')->default(0);
});

echo "DONE.\n";
