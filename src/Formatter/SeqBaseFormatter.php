<?php

namespace StormCode\SeqMonolog\Formatter;

use DateTime;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Utils;
use \Throwable;

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
     * Initializes a new instance of the {@see SeqBaseFormatter} class.
     *
     * @param  int $batchMode The json batch mode.
     */
    function __construct($batchMode)
    {
        $this->appendNewline = false;
        $this->batchMode = $batchMode;
    }

    /**
     * Returns a string with the content type for the seq-formatter.
     *
     * @return string
     */
    public abstract function getContentType() : string;

    /**
     * Normalizes the log record array.
     *
     * @param array $recod The log record to normalize.
     * @return array
     */
    protected function normalize($record, $depth = 0)
    {
        if (!is_array($record) && !$record instanceof \Traversable) {
            /* istanbul ignore next */
            throw new \InvalidArgumentException('Array/Traversable expected, got ' . gettype($record) . ' / ' . get_class($record));
        }

        $normalized = [];

        foreach ($record as $key => $value) {
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
     * @param  array $message     The context array.
     * @return void
     */
    protected abstract function processContext(array &$normalized, array $context);

    /**
     * Processes the log level.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  int   $message     The log level.
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
     * @param  string $message     The log channel name.
     * @return void
     */
    protected abstract function processChannel(array &$normalized, string $name);

    /**
     * Processes the log timestamp.
     *
     * @param  array    &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  DateTime $message     The log timestamp.
     * @return void
     */
    protected abstract function processDatetime(array &$normalized, \Monolog\DateTimeImmutable $datetime);

    /**
     * Processes the extras array.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  array $message     The extras array.
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
     * @param  string $value The string to convert.
     * @return string
     */
    protected static function ConvertSnakeCaseToPascalCase(string $value = null) : string {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}
