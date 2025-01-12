<?php

namespace App\Console\Commands;

use App\Services\CarService;
use Illuminate\Console\Command;

class GetCarState extends Command
{
    protected $signature = 'car:get-state';
    protected $description = 'Get the current state of the car';

    public function handle()
    {
        $carService = new CarService();
        $state = $carService->getState();

        $this->info('Current Car State: ' . json_encode($state, JSON_PRETTY_PRINT));
    }
}
