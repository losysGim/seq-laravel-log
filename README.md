# Laravel Seq Log Handler

This package provides a SeqHandler for the [Monolog](https://github.com/Seldaek/monolog) library and Laravel Framework.
[Seq](https://getseq.net/) is a log server that runs on a central machine.

Prerequisites
-------------

- PHP 8.0 or above.

Installation
------------

Install the latest version with

```bash
$ composer require stormcode/seq-laravel-log
```

Laravel Usage
---------------------
To ```config/logging.php``` add:

```php
'seq' => [
    'driver' => 'monolog',
    'handler' => StormCode\SeqMonolog\Handler\SeqHandler::class,
    'with' => [
        'serverUri' => env('SEQ_URL'),
        'apiKey' => env('SEQ_API_KEY', null),
        'level' => Monolog\Logger::DEBUG,
        'bubble' => true
    ],
    'formatter' => StormCode\SeqMonolog\Formatter\SeqCompactJsonFormatter::class,
    'formatter_with' => [
        'batchMode' => 1, //1 OR 2
    ],
],
```
Then add this to your ```.env``` file:
```.dotenv
LOG_CHANNEL=seq

SEQ_URL=http://localhost:5341/
SEQ_API_KEY=YOUR_API_KEY
```
Now you can freely use seq raporting. If you are using many apps in one seq I suggest to add ```Channel``` variable while defining API_KEY.

License
-------

This project is licensed under the terms of the MIT license.
See the [LICENSE](LICENSE.md) file for license rights and limitations.

Thanks
------
Many thanks to msschl, who created package [msschl/monolog-seq-handler](https://github.com/msschl/monolog-seq-handler) 
and [msschl/monolog-http-handler](https://github.com/msschl/monolog-http-handler), 
which this package is based on. This package is only merge of this two packages and upgrade to php ^8.2 with some changes that was needed to make it work.





