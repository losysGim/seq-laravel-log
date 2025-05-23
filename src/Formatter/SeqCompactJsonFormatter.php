<?php

namespace StormCode\SeqMonolog\Formatter;

use DateTime;
use Monolog\Formatter\JsonFormatter;
use StormCode\SeqMonolog\Exception\WrongCodePathException;

/**
 * This file is part of the stormcode/seq-laravel-log package.
 *
 * Copyright (c) 2018 Markus Schlotbohm & 2024 Mikołaj Salamak
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */
class SeqCompactJsonFormatter extends SeqBaseFormatter
{

    /**
     * The extract context flag.
     * Whether to extract the context array to the root or not.
     *
     * @var bool
     */
    protected $extractContext;

    /**
     * The extract extras flag.
     * Whether to extract the extras array to the root or not.
     *
     * @var bool
     */
    protected $extractExtras;

    /**
     * Initializes a new instance of the {@see SeqCompactJsonFormatter} class.
     *
     * @param  bool $extractContext Flag that indicates whether to extract the extras array
     *                              to the root or not.
     * @param  bool $extractExtras  Flag that indicates whether to extract the context array
     *                              to the root or not.
     */
	public function __construct(bool $extractContext = true, bool $extractExtras = true)
	{
        $this->extractContext = $extractContext;
        $this->extractExtras = $extractExtras;

        parent::__construct(JsonFormatter::BATCH_MODE_NEWLINES);
	}

    /**
     * Returns a string with the content type for the seq-formatter.
     *
     * @return string
     */
    public function getContentType() : string {
        return 'application/vnd.serilog.clef';
    }

    /**
     * Gets whether the flag extract content is set or not.
     *
     * @return bool
     */
    public function getExtractContent() : bool
    {
        return $this->extractContext;
    }

    /**
     * Sets the flag extract content.
     *
     * @param  bool $value The flag.
     * @return self
     */
    public function setExtractContent(bool $value)
    {
        $this->extractContext = $value;

        return $this;
    }

    /**
     * Gets whether the flag extract extras is set or not.
     *
     * @return bool
     */
    public function getExtractExtras()
    {
        return $this->extractExtras;
    }

    /**
     * Sets the flag extract extras.
     *
     * @param  bool $value The flag.
     * @return self
     */
    public function setExtractExtras(bool $value)
    {
        $this->extractExtras = $value;

        return $this;
    }

    /**
     * This function should never be called!!!
     *
     * @throws WrongCodePathException
     */
    protected function formatBatchJson(array $records): string
    {
        /* istanbul ignore next */
        throw new WrongCodePathException();
    }

    /**
     * Processes the log message.
     *
     * @param  array  &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  string $message     The log message.
     * @return void
     */
    protected function processMessage(array &$normalized, string $message)
    {
        $normalized['@m'] = $message;
        if (!(strpos($message, '{') === false)) {
            $normalized['@mt'] = $message;
        }
    }

    /**
     * Processes the context array.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  array $context     The context array.
     * @return void
     */
    protected function processContext(array &$normalized, array $context)
    {
        $this->processContextException($normalized, $context);
        $array = $this->getNormalizedArray($context);

        if ($this->extractContext) {
            $normalized = array_merge($array, $normalized);
        } else {
            $normalized['Context'] = $array;
        }
    }

    /**
     * Processes the log level.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  int   $level       The log level.
     * @return void
     */
    protected function processLevel(array &$normalized, int $level)
    {
        $normalized['@l'] = $this->logLevelMap[$level];
        $normalized['Code'] = $level;
    }

    /**
     * Processes the log level name.
     *
     * @param  array  &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  string $levelName   The log level name.
     * @return void
     */
    protected function processLevelName(array &$normalized, string $levelName)
    {
        $normalized['LevelName'] = $levelName;
    }

    /**
     * Processes the channel name.
     *
     * @param  array  &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  string $name        The log channel name.
     * @return void
     */
    protected function processChannel(array &$normalized, string $name)
    {
        $normalized['Channel'] = $name;
    }

    /**
     * Processes the log timestamp.
     *
     * @param  array             &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  \DateTimeInterface $datetime   The log timestamp.
     * @return void
     */
    protected function processDatetime(array &$normalized, \DateTimeInterface $datetime)
    {
        $normalized['@t'] = $datetime->format(DateTime::ISO8601);
    }

    /**
     * Processes the extras array.
     *
     * @param  array &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  array $extras      The extras array.
     * @return void
     */
    protected function processExtra(array &$normalized, array $extras)
    {
        $array = $this->getNormalizedArray($extras);

        if ($this->extractExtras) {
            $normalized = array_merge($array, $normalized);
        } else {
            $normalized['Extra'] = $array;
        }
    }

    /**
     * Extracts the exception from the context array.
     *
     * @param  array  &$normalized Reference to the normalized array, where all normalized data get stored.
     * @param  array  $context     The context array.
     * @return void
     */
    private function processContextException(array &$normalized, array $context)
    {
        $exception = $this->extractException($context);
        if ($exception !== null) {
            $x = $this->normalizeException($exception);
            if(isset($x['previous'])) {
                if(isset($x['previous']['trace'])) {
                    $x['previous']['trace'] = implode(PHP_EOL, $x['previous']['trace']);
                }
                $previousEnd = '';
                foreach ($x['previous'] as $key => $val) {
		    if (is_array($val)) $val = json_encode($val);
                    $previousEnd .= "\t".$key . ': ' . $val . PHP_EOL;
                }
                $x['previous'] = $previousEnd;
            }
            if(isset($x['trace']) && is_array($x['trace'])) {
                $x['trace'] = implode(PHP_EOL, $x['trace']);
            }
            $xEnd = '';
            foreach($x as $key => $val) {
                $xEnd .= $key.': '.$val.PHP_EOL;
            }
            $normalized['@x'] = $xEnd;
        }
    }

    /**
     * Gets a normalized array.
     *
     * @param  array $array The array to process.
     * @return array
     */
    private function getNormalizedArray(array $array) : array
    {
        $normalized = [];
        $count = 1;
        foreach ($array as $key => $value) {
            if ($count++ >= 1000) {
                $normalized['...'] = 'Over 1000 items, aborting normalization';
                break;
            }

            if (is_int($key)) {
                $normalized[] = $value;
            } else {
                $key = SeqCompactJsonFormatter::ConvertSnakeCaseToPascalCase($key);
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
