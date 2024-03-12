<?php

namespace StormCode\SeqMonolog\Exception;

use Exception;


/**
 * This file is part of the stormcode/seq-laravel-log package.
 *
 * Copyright (c) 2018 Markus Schlotbohm & 2024 Mikołaj Salamak
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */
class WrongCodePathException extends Exception
{
	public function __construct()
	{
		return parent::__construct('Wrong code path!');
	}
}
