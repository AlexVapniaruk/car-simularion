<?php
namespace App\Services;

use Illuminate\Support\Facades\Session;

class CarService
{
    protected $state;

    public function __construct()
    {
        $this->state = Session::get('car_state', $this->initializeState());
    }

    protected function initializeState()
    {
        return [
            'doors' => 'locked',
            'car_on' => false,
            'entertainment_unit' => 'radio',
            'fuel_level' => 10,
            'windows' => ['left' => 100, 'right' => 100],
            'driving' => false,
            'odometer' => 0,
        ];
    }

    public function getState()
    {
        return $this->state;
    }

    public function updateState(array $updates)
    {
        $this->state = array_merge($this->state, $updates);
        Session::put('car_state', $this->state);
    }

    public function resetState()
    {
        $this->state = $this->initializeState();
        Session::put('car_state', $this->state);
    }

    public function handleEvent(string $event, $value = null)
    {
        switch ($event) {
            case 'driver-unlocks-doors':
                $this->state['doors'] = 'unlocked';
                break;

            case 'driver-locks-doors':
                $this->state['doors'] = 'locked';
                break;

            case 'driver-turns-car-on':
                $this->state['car_on'] = true;
                break;

            case 'driver-turns-car-off':
                $this->state['car_on'] = false;
                $this->state['driving'] = false;
                break;

            case 'driver-listen-radio':
                if ($this->state['car_on']) {
                    $this->state['entertainment_unit'] = 'radio';
                }
                break;

            case 'driver-listen-cd':
                if ($this->state['car_on']) {
                    $this->state['entertainment_unit'] = 'cd';
                }
                break;

            case 'driver-listen-spotify':
                if ($this->state['car_on']) {
                    $this->state['entertainment_unit'] = 'spotify';
                }
                break;

            case 'add-fuel':
                if (!$this->state['driving'] && is_numeric($value)) {
                    $newFuelLevel = $this->state['fuel_level'] + (50 * $value);
                    $this->state['fuel_level'] = min(50, $newFuelLevel);
                }
                break;

            case 'driver-lowers-windows':
                if ($this->state['car_on'] && isset($this->state['windows'][$value])) {
                    $this->state['windows'][$value] = max(0, $this->state['windows'][$value] - 50);
                }
                break;

            case 'driver-raises-windows':
                if ($this->state['car_on'] && isset($this->state['windows'][$value])) {
                    $this->state['windows'][$value] = min(100, $this->state['windows'][$value] + 50);
                }
                break;

            case 'drive':
                if ($value === 'drive' && $this->state['car_on'] && !$this->state['driving'] && $this->state['fuel_level'] >= 2.5) {
                    $this->state['driving'] = true;
                    $this->state['fuel_level'] -= 2.5; // Consumes 2.5L for 25km
                    $this->state['odometer'] += 25;
                } elseif ($value === 'stop') {
                    $this->state['driving'] = false;
                }
                break;

            default:
                break;
        }
    }
}
