<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when a roster change breaks a rule. The message is written for the end user. */
class RosterException extends RuntimeException
{
}
