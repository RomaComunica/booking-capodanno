<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

try {
    echo "Initializing Local SQLite Database manually...\n";
    
    $db = $app->make('db');
    $connection = $db->connection();
    $schema = $connection->getSchemaBuilder();

    echo "Database: " . $connection->getDatabaseName() . "\n";
    echo "Database file size: " . filesize($connection->getDatabaseName()) . "\n";
    
    // Disable FKs for drop
    $connection->statement('PRAGMA foreign_keys = OFF');
    echo "Foreign keys disabled.\n";

    $tables = [
        'users', 
        'cache', 'cache_locks', 
        'jobs', 'job_batches', 'failed_jobs',
        'categorie_location', 'tipologie_location', 'location',
        'cross_location_categorie', 'cross_location_tipologie',
        'cross_pagamenti_location', 'cross_spedizioni_location',
        'tipologia_ingresso', 'cross_partner_ingressi'
    ];

    foreach ($tables as $table) {
        $schema->dropIfExists($table);
    }
    
    echo "Tables dropped.\n";

    // Users
    echo "Creating users...\n";
    $schema->create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email', 191)->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
    echo "Users table created.\n";

    // Cache
    echo "Creating cache...\n";
    $schema->create('cache', function (Blueprint $table) {
        $table->string('key', 191)->primary();
        $table->mediumText('value');
        $table->integer('expiration');
    });
    $schema->create('cache_locks', function (Blueprint $table) {
        $table->string('key', 191)->primary();
        $table->string('owner');
        $table->integer('expiration');
    });

    // Jobs
    echo "Creating jobs...\n";
    $schema->create('jobs', function (Blueprint $table) {
        $table->id();
        $table->string('queue', 191)->index();
        $table->longText('payload');
        $table->unsignedTinyInteger('attempts');
        $table->unsignedInteger('reserved_at')->nullable();
        $table->unsignedInteger('available_at');
        $table->unsignedInteger('created_at');
    });
    $schema->create('job_batches', function (Blueprint $table) {
        $table->string('id', 191)->primary();
        $table->string('name');
        $table->integer('total_jobs');
        $table->integer('pending_jobs');
        $table->integer('failed_jobs');
        $table->longText('failed_job_ids');
        $table->mediumText('options')->nullable();
        $table->integer('cancelled_at')->nullable();
        $table->integer('created_at');
        $table->integer('finished_at')->nullable();
    });
    $schema->create('failed_jobs', function (Blueprint $table) {
        $table->id();
        $table->string('uuid', 191)->unique();
        $table->text('connection');
        $table->text('queue');
        $table->longText('payload');
        $table->longText('exception');
        $table->timestamp('failed_at')->useCurrent();
    });

    // Categorie & Tipologie Location
    echo "Creating categorie_location & tipologie_location...\n";
    $schema->create('categorie_location', function (Blueprint $table) {
        $table->integer('idCatLocation')->primary()->autoIncrement();
        $table->string('nomeCatLocation', 255);
    });
    $schema->create('tipologie_location', function (Blueprint $table) {
        $table->integer('idTipLocation')->primary()->autoIncrement();
        $table->string('nomeTipLocation', 255);
    });

    // Location
    echo "Creating location...\n";
    $schema->create('location', function (Blueprint $table) {
        $table->integer('idLocation')->primary()->autoIncrement();
        $table->string('nomeLocation', 255);
        $table->string('indirizzoLocation', 255)->nullable();
        $table->string('telefonoLocation', 50)->nullable();
        $table->string('emailLocation', 100)->nullable();
        $table->string('riferimentoLocation', 100)->nullable();
        $table->string('telefonoRifLocation', 50)->nullable();
        $table->string('emailRifLocation', 100)->nullable();
        $table->boolean('attivaLocation')->default(1);
    });
    echo "Location table created.\n";

    // Cross Tables
    echo "Creating cross tables...\n";
    $schema->create('cross_location_categorie', function (Blueprint $table) {
        $table->integer('idLocation');
        $table->integer('idCatLocation');
        $table->primary(['idLocation', 'idCatLocation']);
        // SQLite supports simple FKs usually
    });
    $schema->create('cross_location_tipologie', function (Blueprint $table) {
        $table->integer('idLocation');
        $table->integer('idTipLocation');
        $table->primary(['idLocation', 'idTipLocation']);
    });
    $schema->create('cross_pagamenti_location', function (Blueprint $table) {
        $table->integer('idLocationPagamento');
        $table->integer('idPagamento');
        $table->boolean('pagamentoLocationAttivo')->default(1);
        $table->decimal('costoPagamento', 10, 2)->nullable();
        $table->primary(['idLocationPagamento', 'idPagamento']);
    });
    $schema->create('cross_spedizioni_location', function (Blueprint $table) {
        $table->integer('idLocationSpedizione');
        $table->integer('idSpedizione');
        $table->boolean('spedizioneLocationAttiva')->default(1);
        $table->primary(['idLocationSpedizione', 'idSpedizione']);
    });
    echo "Cross tables created.\n";

    // Tipologia Ingresso
    echo "Creating tipologia_ingresso...\n";
    $schema->create('tipologia_ingresso', function (Blueprint $table) {
        $table->integer('idIngressoCont')->primary()->autoIncrement();
        $table->integer('idIngresso');
        $table->integer('idLocation');
        $table->string('nomeIngresso', 255);
        $table->decimal('prezzoIngresso', 10, 2)->default(0);
        $table->integer('ordine')->default(0); 
        $table->integer('giorniPrimaIngresso')->default(0);
    });

    // Cross Partner Ingressi
    echo "Creating cross_partner_ingressi...\n";
    $schema->create('cross_partner_ingressi', function (Blueprint $table) {
        $table->integer('idPartnerIngressi');
        $table->integer('idTipologiaIngresso');
        $table->integer('quantitaIngressi')->default(0);
        $table->integer('idLocation');
        $table->primary(['idPartnerIngressi', 'idTipologiaIngresso']);
    });

    // Seed User
    echo "Seeding Admin User...\n";
    User::updateOrCreate(
        ['email' => 'admin@capodannoroma.it'],
        [
            'name' => 'Admin',
            'password' => Hash::make('password'),
        ]
    );

    echo "SUCCESS! Database initialized.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "In File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
