<?php
namespace Tests\Feature;

use App\Services\CarService;
use App\Events\CarStateChanged;
use Tests\TestCase;

class CarServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure state is reset for each test
        $this->carService = new CarService();

        // Ensure state is reset for each test
        $this->carService->resetState();
    }

    public function testCarServiceInitializesCorrectState()
    {
        $carService = new CarService();

        $expectedState = [
            'doors' => 'locked',
            'car_on' => false,
            'entertainment_unit' => 'radio',
            'fuel_level' => 10,
            'windows' => ['left' => 100, 'right' => 100],
            'driving' => false,
            'odometer' => 0,
        ];

        $this->assertEquals($expectedState, $carService->getState());
    }

    public function testCarServiceLocksAndUnlocksDoors()
    {
        $carService = new CarService();

        // Unlock doors
        $carService->handleEvent('driver-unlocks-doors');
        $this->assertEquals('unlocked', $carService->getState()['doors']);

        // Lock doors
        $carService->handleEvent('driver-locks-doors');
        $this->assertEquals('locked', $carService->getState()['doors']);
    }

    public function testCarServiceTurnsCarOnAndOff()
    {
        $carService = new CarService();

        // Turn car on
        $carService->handleEvent('driver-turns-car-on');
        $this->assertTrue($carService->getState()['car_on']);

        // Turn car off
        $carService->handleEvent('driver-turns-car-off');
        $this->assertFalse($carService->getState()['car_on']);
    }

    public function testCarServiceHandlesWindows()
    {
        $carService = new CarService();

        // Turn the car on before adjusting windows
        $carService->handleEvent('driver-turns-car-on');

        // Lower left window
        $carService->handleEvent('driver-lowers-windows', 'left');
        $this->assertEquals(50, $carService->getState()['windows']['left']);
        $this->assertEquals(100, $carService->getState()['windows']['right']);

        // Raise left window
        $carService->handleEvent('driver-raises-windows', 'left');
        $this->assertEquals(100, $carService->getState()['windows']['left']);
    }

    public function testCarServiceHandlesFuel()
    {
        $carService = new CarService();

        // Add fuel
        $carService->handleEvent('add-fuel', 0.2); // Adding 20% of full capacity (10 liters)
        $this->assertEquals(20, $carService->getState()['fuel_level']);

        // Ensure fuel doesn't exceed capacity
        $carService->handleEvent('add-fuel', 1); // Adding full capacity
        $this->assertEquals(50, $carService->getState()['fuel_level']);
    }

    public function testCarServiceHandlesDriving()
    {
        $carService = new CarService();

        // Initial state of the car
        $this->assertEquals(10, $carService->getState()['fuel_level']);

        // Turn the car on and drive
        $carService->handleEvent('driver-turns-car-on');
        $carService->handleEvent('drive', 'drive');

        // Assert the fuel level after driving
        $this->assertEquals(7.5, $carService->getState()['fuel_level']);
        $this->assertEquals(25, $carService->getState()['odometer']);

        // Stop the car
        $carService->handleEvent('drive', 'stop');
    }

    public function testCarServiceCannotDriveWithInsufficientFuel()
    {
        $carService = new CarService();

        // Set fuel level lower than needed for driving
        $carService->updateState(['fuel_level' => 2]);

        $this->expectException(\Exception::class);
        $carService->handleEvent('drive', 'drive'); // Should throw exception as fuel is insufficient
    }

    public function testCarServiceFuelOverflow()
    {
        $carService = new CarService();

        // Add fuel that exceeds max capacity
        $carService->handleEvent('add-fuel', 2); // Add 2 times the max amount

        $this->assertEquals(50, $carService->getState()['fuel_level']); // Fuel should be capped at 50
    }

    public function testCarCannotTurnOnWithoutEnoughFuel()
    {
        $carService = new CarService();

        // Set fuel level to 0
        $carService->updateState(['fuel_level' => 0]);

        // Try turning on the car
        $carService->handleEvent('driver-turns-car-on');
        $this->assertFalse($carService->getState()['car_on']); // Car should remain off
    }

    public function testCarServiceResetState()
    {
        $carService = new CarService();

        // Modify state
        $carService->updateState(['fuel_level' => 20]);

        // Reset state
        $carService->resetState();

        $this->assertEquals(10, $carService->getState()['fuel_level']); // Fuel level should be reset to 10
    }

    public function testCarServiceCannotLowerWindowsBeyondZero()
    {
        $carService = new CarService();

        // Turn the car on first
        $carService->handleEvent('driver-turns-car-on');

        // Lower the window once
        $carService->handleEvent('driver-lowers-windows', 'left');

        // Lower the window again, it should not go below 0
        $carService->handleEvent('driver-lowers-windows', 'left');

        // Assert that the window is at 0, not negative
        $this->assertEquals(0, $carService->getState()['windows']['left']);
    }

    public function testCarServiceCannotRaiseWindowsBeyond100()
    {
        $carService = new CarService();

        // Turn the car on first
        $carService->handleEvent('driver-turns-car-on');

        // Raise the window once
        $carService->handleEvent('driver-raises-windows', 'left');

        // Raise the window again, it should not go above 100
        $carService->handleEvent('driver-raises-windows', 'left');

        // Assert that the window is at 100, not more
        $this->assertEquals(100, $carService->getState()['windows']['left']);
    }

    public function testCarServiceCannotLowerWindowsWithoutTurningCarOn()
    {
        $carService = new CarService();

        // Try lowering the window without turning the car on
        $carService->handleEvent('driver-lowers-windows', 'left');

        // Assert that the window hasn't changed because the car is off
        $this->assertEquals(100, $carService->getState()['windows']['left']); // Window should remain at 100
    }

    public function testCarServiceCanLowerWindowsWhenCarIsOn()
    {
        $carService = new CarService();

        // Turn the car on first
        $carService->handleEvent('driver-turns-car-on');

        // Now try lowering the window
        $carService->handleEvent('driver-lowers-windows', 'left');

        // Assert that the window has lowered by 50
        $this->assertEquals(50, $carService->getState()['windows']['left']);
    }

    public function testCarServiceCannotRaiseWindowsWithoutTurningCarOn()
    {
        $carService = new CarService();

        // Try raising the window without turning the car on
        $carService->handleEvent('driver-raises-windows', 'left');

        // Assert that the window hasn't changed because the car is off
        $this->assertEquals(100, $carService->getState()['windows']['left']); // Window should remain at 100
    }

    public function testCarServiceCanRaiseWindowsWhenCarIsOn()
    {
        $carService = new CarService();

        // Turn the car on first
        $carService->handleEvent('driver-turns-car-on');

        // Now try raising the window
        $carService->handleEvent('driver-raises-windows', 'left');

        // Assert that the window has raised by 50
        $this->assertEquals(100, $carService->getState()['windows']['left']);
    }

    public function testCarServiceCanTurnCarOff()
    {
        $carService = new CarService();

        // Turn the car on first
        $carService->handleEvent('driver-turns-car-on');

        // Now turn the car off
        $carService->handleEvent('driver-turns-car-off');

        // Assert that the car is off
        $this->assertFalse($carService->getState()['car_on']);
    }
}
