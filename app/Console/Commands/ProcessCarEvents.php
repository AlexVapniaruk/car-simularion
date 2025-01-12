<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\CarService;

class ProcessCarEvents extends Command
{
    protected $signature = 'car:process-events';
    protected $description = 'Process car events from CSV files';

    public function handle()
    {
        $eventFiles = Storage::files('events');
        $carService = new CarService();

        foreach ($eventFiles as $file) {
            if (Storage::exists($file)) {
                $this->info("Processing: $file");

                $content = Storage::get($file);
                $lines = array_map('str_getcsv', explode("\n", $content));

                foreach ($lines as $line) {
                    if (count($line) < 1) continue;

                    $event = $line[0];
                    $value = isset($line[1]) ? $line[1] : null;

                    // Update car state
                    $carService->handleEvent($event, $value);
                }

                // Save state
                $carService->updateState([]);
                $this->info("Updated state: " . json_encode($carService->getState()));

                // Move file to processed folder
                Storage::move($file, 'events/processed/' . basename($file));
                $this->info("File moved to processed: " . basename($file));
            }
        }
    }
}
