<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown when an entry or withdrawal breaks a rule. The message is written for the end user. */
class EntryException extends RuntimeException
{
}
