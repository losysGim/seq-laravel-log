<?php

namespace StormCode\SeqMonolog\Formatter;

use InvalidArgumentException;
use Monolog\Formatter\JsonFormatter;
use Monolog\Utils;
use \Throwable;
use Traversable;

/**
 * This file is part of the stormcode/seq-laravel-log package.
 *
 * Copyright (c) 2018 Markus Schlotbohm & 2024 Mikołaj Salamak
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

abstract class SeqBaseFormatter extends JsonFormatter
{

    /**
     * Log Level Mapping
     *
     * @var array
     */
    protected $logLevelMap = [
        '100' => 'Debug',
        '200' => 'Information',
        '250' => 'Information',
        '300' => 'Warning',
        '400' => 'Error',
        '500' => 'Error',
        '550' => 'Fatal',
        '600' => 'Fatal',
    ];

    /**
     * Returns a string with the content type for the seq-formatter.
     *
     * @return string
     */
    public abstract function getContentType() : string;

    /**
     * Normalizes the log record array.
     *
     * @param mixed $data The log record to normalize.
     * @param int   $depth  unused
     * @return mixed
     */
    protected function normalize(mixed $data, int $depth = 0): mixed
    {
        if (!is_array($data) && !$data instanceof Traversable) {
            /* istanbul ignore next */
            throw new InvalidArgumentException('Array/Traversable expected, got ' . gettype($data) . ' / ' . get_class($data));
        }

        $normalized = [];

        foreach ($data as $key => $value) {
            $key = SeqBaseFormatter::ConvertSnakeCaseToPascalCase($key);

            $this->{'process' . $key}($normalized, $value);
        }

        return $normalized;
    }

    /**
     * Processes the log message.
     *
     * @param  array  &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  string $message     The log message.
     * @return void
     */
    protected abstract function processMessage(array &$normalized, string $message);

    /**
     * Processes the context array.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  array $context     The context array.
     * @return void
     */
    protected abstract function processContext(array &$normalized, array $context);

    /**
     * Processes the log level.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  int   $level       The log level.
     * @return void
     */
    protected abstract function processLevel(array &$normalized, int $level);

    /**
     * Processes the log level name.
     *
     * @param  array  &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  string $message     The log level name.
     * @return void
     */
    protected abstract function processLevelName(array &$normalized, string $levelName);

    /**
     * Processes the channel name.
     *
     * @param  array  &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  string $name        The log channel name.
     * @return void
     */
    protected abstract function processChannel(array &$normalized, string $name);

    /**
     * Processes the log timestamp.
     *
     * @param  array              &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  \DateTimeInterface $datetime     The log timestamp.
     * @return void
     */
    protected abstract function processDatetime(array &$normalized, \DateTimeInterface $datetime);

    /**
     * Processes the extras array.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  array $extras      The extras array.
     * @return void
     */
    protected abstract function processExtra(array &$normalized, array $extras);

    /**
     * Normalizes an exception to a string.
     *
     * @param  Throwable $e The throwable instance to normalize.
     * @return array
     */
    protected function normalizeException(Throwable $e, int $depth = 0): array
    {
        if ($depth > $this->maxNormalizeDepth) {
            return ['Over ' . $this->maxNormalizeDepth . ' levels deep, aborting normalization'];
        }

        if ($e instanceof \JsonSerializable) {
            return (array) $e->jsonSerialize();
        }

        $data = [
            'class' => Utils::getClass($e),
            'message' => $e->getMessage(),
            'code' => (int) $e->getCode(),
            'file' => $e->getFile().':'.$e->getLine(),
        ];

        if ($e instanceof \SoapFault) {
            if (isset($e->faultcode)) {
                $data['faultcode'] = $e->faultcode;
            }

            if (isset($e->faultactor)) {
                $data['faultactor'] = $e->faultactor;
            }

            if (isset($e->detail)) {
                if (is_string($e->detail)) {
                    $data['detail'] = $e->detail;
                } elseif (is_object($e->detail) || is_array($e->detail)) {
                    $data['detail'] = $this->toJson($e->detail, true);
                }
            }
        }

        $trace = $e->getTrace();
        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $data['trace'][] = $frame['file'].':'.$frame['line'];
            }
        }

        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->normalizeException($previous, $depth + 1);
        }

        return $data;
    }

    /**
     * Extracts the exception from an array.
     *
     * @param  array  &$array The array.
     * @return \Throwable|null
     */
    protected function extractException(array &$array) {
        $exception = $array['exception'] ?? null;

        if ($exception === null) {
            return null;
        }

        unset($array['exception']);

        if (!($exception instanceof \Throwable)) {
            return null;
        }

        return $exception;
    }

    /**
     * Converts a snake case string to a pascal case string.
     *
     * @param  string|null $value The string to convert.
     * @return string
     */
    protected static function ConvertSnakeCaseToPascalCase(?string $value = null) : string {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}
