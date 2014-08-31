<?php

use Ratchet\Session\SessionProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;
use Ratchet\App;
use App\Mains;

date_default_timezone_set('Asia/Jakarta');

require dirname(__DIR__) . '/vendor/autoload.php';

$memcache = new Memcache;
$memcache->connect('localhost', 11211);

$mains = new SessionProvider(
    new Mains,
    new Handler\MemcacheSessionHandler($memcache)
);

$app = new App();
$app->route('POS/Mains', $mains);
$app->run();