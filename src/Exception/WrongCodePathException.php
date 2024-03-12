<?php

namespace StormCode\SeqMonolog\Exception;

use Exception;

class WrongCodePathException extends Exception
{
	public function __construct()
	{
		return parent::__construct('Wrong code path!');
	}
}
