<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Models\Cliente;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        $existing = DB::table('clienti')
            ->whereRaw('LOWER(TRIM(emailCliente)) = ?', [$email])
            ->first();

        $payload = [
            'nomeCliente' => trim((string) ($data['nome'] ?? '')),
            'cognomeCliente' => trim((string) ($data['cognome'] ?? '')),
            'emailCliente' => $email,
            'telefonoCliente' => trim((string) ($data['telefono'] ?? '')),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('clienti', 'cittaCliente')) {
            $payload['cittaCliente'] = trim((string) ($data['citta'] ?? ''));
        }

        if ($existing) {
            DB::table('clienti')
                ->where('idCliente', $existing->idCliente)
                ->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('clienti')->insert($payload);
        }

        return Cliente::where('email', $email)->first() ?? new Cliente([
            'email' => $email,
            'nome' => $payload['nomeCliente'],
            'cognome' => $payload['cognomeCliente'],
            'telefono' => $payload['telefonoCliente'],
            'numero_ordini' => 0,
            'totale_speso' => 0,
            'ultimo_ordine' => now(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
