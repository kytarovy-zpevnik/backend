<?php

namespace App;

use Kdyby\Doctrine\DuplicateEntryException;
use RuntimeException;

class SecurityException extends RuntimeException { }

class DuplicateUsernameException extends RuntimeException { }

class DuplicateEmailException extends RuntimeException { }
