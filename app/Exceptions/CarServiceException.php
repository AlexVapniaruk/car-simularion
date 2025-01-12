<?php

namespace App\Exceptions;

use Exception;

class CarServiceException extends Exception
{
    // Optionally, you can add custom properties to the exception
    protected $message = 'Car Service Error'; // Default message
    protected $code = 400; // Default status code (optional)

    /**
     * Create a new CarServiceException instance.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct($message = null, $code = null)
    {
        // If a custom message is passed, use it, otherwise use the default one
        if ($message) {
            $this->message = $message;
        }

        // If a custom code is passed, use it, otherwise use the default one
        if ($code) {
            $this->code = $code;
        }

        parent::__construct($this->message, $this->code);
    }

    // Optionally, you can also customize the exception's rendering or logging
    public function report()
    {
        // Log the exception or handle reporting if necessary
    }

    public function render($request)
    {
        // Return a custom response, for example:
        return response()->json(['error' => $this->message], $this->code);
    }
}

