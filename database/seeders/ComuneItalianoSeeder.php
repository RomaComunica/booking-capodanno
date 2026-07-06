<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\ComuneItaliano;

class ComuneItalianoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Caricamento lista comuni italiani...');
        
        $jsonPath = database_path('comuni.json');
        
        if (file_exists($jsonPath)) {
            $jsonContent = file_get_contents($jsonPath);
            $comuni = json_decode($jsonContent, true);
            
            $this->command->info('Comuni trovati: ' . count($comuni));
            $this->command->info('Salvataggio nel database...');
            
            DB::table('comuni_italiani')->truncate();
            
            $data = [];
            foreach ($comuni as $comune) {
                $data[] = [
                    'nomeComune' => $comune['nome'],
                    'siglaProvincia' => $comune['sigla'],
                ];
                
                if (count($data) >= 500) {
                    DB::table('comuni_italiani')->insert($data);
                    $data = [];
                }
            }
            if (count($data) > 0) {
                DB::table('comuni_italiani')->insert($data);
            }
            
            $this->command->info('Seeding completato!');
        } else {
            $this->command->error('File database/comuni.json non trovato.');
        }
    }
}
