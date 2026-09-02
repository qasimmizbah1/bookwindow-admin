<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\State;
use App\Models\City;

class ImportStatesAndCities extends Command
{
    protected $signature = 'import:states-cities {file}';
    protected $description = 'Import states and cities from JSON file';

    public function handle()
    {
        $file = $this->argument('file');
        $data = json_decode(file_get_contents($file), true);
        
        foreach ($data['states'] as $stateData) {
            $state = State::create(['name' => $stateData['name']]);
            
            foreach ($stateData['cities'] as $cityName) {
                City::create([
                    'state_id' => $state->id,
                    'name' => $cityName
                ]);
            }
        }
        
        $this->info('States and cities imported successfully!');
    }
}