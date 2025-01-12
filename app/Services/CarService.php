<?php
namespace App\Services;

use Illuminate\Support\Facades\Session;
use App\Exceptions\CarServiceException;

class CarService
{
    protected $state;

    const MAX_FUEL_CAPACITY = 50;
    const FUEL_ECONOMY = 2.5;  // Fuel consumption per 25km drive
    const MAX_WINDOW_POSITION = 100;
    const MIN_WINDOW_POSITION = 0;
    const FUEL_THRESHOLD = 2.5; // Minimum fuel level to start the car

    // Constants for states and events
    const STATE_LOCKED = 'locked';
    const STATE_UNLOCKED = 'unlocked';
    const STATE_CAR_OFF = false;
    const STATE_CAR_ON = true;

    const EVENTS = [
        'driver-unlocks-doors' => 'unlockDoors',
        'driver-locks-doors' => 'lockDoors',
        'driver-turns-car-on' => 'turnCarOn',
        'driver-turns-car-off' => 'turnCarOff',
        'driver-listen-radio' => 'listenRadio',
        'driver-listen-cd' => 'listenCd',
        'driver-listen-spotify' => 'listenSpotify',
        'add-fuel' => 'addFuel',
        'driver-lowers-windows' => 'lowerWindows',
        'driver-raises-windows' => 'raiseWindows',
        'drive' => 'drive'
    ];

    public function __construct()
    {
        $this->state = Session::get('car_state', $this->initializeState());
    }

    protected function initializeState()
    {
        return [
            'doors' => self::STATE_LOCKED,
            'car_on' => self::STATE_CAR_OFF,
            'entertainment_unit' => 'radio',
            'fuel_level' => self::FUEL_THRESHOLD * 4,  // 10 as initial fuel level
            'windows' => ['left' => self::MAX_WINDOW_POSITION, 'right' => self::MAX_WINDOW_POSITION],
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
        foreach ($updates as $key => $value) {
            $this->state[$key] = $value;
        }

        Session::put('car_state', $this->state);
    }

    public function resetState()
    {
        $this->state = $this->initializeState();
        Session::put('car_state', $this->state);
    }

    public function handleEvent(string $event, $value = null)
    {
        if (!array_key_exists($event, self::EVENTS)) {
            throw new CarServiceException("Invalid event: $event");
        }

        $method = self::EVENTS[$event];
        $this->$method($value);
    }

    // Event Methods
    protected function unlockDoors()
    {
        $this->state['doors'] = self::STATE_UNLOCKED;
    }

    protected function lockDoors()
    {
        $this->state['doors'] = self::STATE_LOCKED;
    }

    protected function turnCarOn()
    {
        if ($this->isFuelLow()) {
            $this->state['car_on'] = self::STATE_CAR_OFF;
        } else {
            $this->state['car_on'] = self::STATE_CAR_ON;
        }
    }

    protected function turnCarOff()
    {
        $this->state['car_on'] = self::STATE_CAR_OFF;
        $this->state['driving'] = false;
    }

    protected function listenRadio()
    {
        $this->setEntertainmentUnit('radio');
    }

    protected function listenCd()
    {
        $this->setEntertainmentUnit('cd');
    }

    protected function listenSpotify()
    {
        $this->setEntertainmentUnit('spotify');
    }

    protected function addFuel($value)
    {
        if ($this->state['driving']) {
            throw new CarServiceException('Add fuel not possible when driving');
        }

        if (is_numeric($value)) {
            $newFuelLevel = $this->state['fuel_level'] + (self::MAX_FUEL_CAPACITY * $value);
            $this->state['fuel_level'] = min(self::MAX_FUEL_CAPACITY, $newFuelLevel);
        }
    }

    protected function lowerWindows($window)
    {
        if ($this->state['car_on'] && isset($this->state['windows'][$window])) {
            $this->state['windows'][$window] = max(self::MIN_WINDOW_POSITION, $this->state['windows'][$window] - 50);
        }
    }

    protected function raiseWindows($window)
    {
        if ($this->state['car_on'] && isset($this->state['windows'][$window])) {
            $this->state['windows'][$window] = min(self::MAX_WINDOW_POSITION, $this->state['windows'][$window] + 50);
        }
    }

    protected function drive($action)
    {
        if ($this->isFuelLow()) {
            throw new CarServiceException('Not enough fuel to drive.');
        }

        if ($action === 'drive' && $this->state['car_on'] && !$this->state['driving']) {
            $this->state['driving'] = true;
            $this->state['fuel_level'] -= self::FUEL_ECONOMY;
            $this->state['odometer'] += 25;
        } elseif ($action === 'stop') {
            $this->state['driving'] = false;
        }
    }

    protected function isFuelLow()
    {
        return $this->state['fuel_level'] < self::FUEL_THRESHOLD;
    }

    protected function setEntertainmentUnit($unit)
    {
        if ($this->state['car_on']) {
            $this->state['entertainment_unit'] = $unit;
        }
    }
}

