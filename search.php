<?php
if (isset($argv[1])) {
    $url = $argv[1];
} else {
    echo "Usage: \n $ php search.php http://www.aruodas.lt/?FAreaOverAllMin=50&FDistrict=1&obj=1&FPriceMax=90000&FQuartal%5B0%5D=23&FRegion=461&FRoomNumMin=3&mod=Siulo&act=makeSearch&date_from=1471866843 '/ulvydo|nedidelis/'\n";
    exit(1);
}

$spamFilter = null;
if (isset($argv[2])) {
    $spamFilter = $argv[2];
}

$cacheDir = __DIR__ . '/var/cache';
$resultDir = __DIR__ . '/var/result';

function request($url) {
    echo "Querying $url...\n";
    $ch = curl_init(); 

    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36");
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch); 
    curl_close($ch);  

    return $html;
}

function cleanup($string) {
    return str_replace(["\n", "  "], '', $string);
}

function latestResult($path) {
    $mtime = 0;
    $filename = null;
    $filepath = null;

    $d = dir($path);
    while (false !== ($entry = $d->read())) {
        if (in_array($entry, ['.', '..'])) {
            continue;
        }
        $filepath = "{$path}/{$entry}";

        if (is_file($filepath) && filemtime($filepath) > $mtime) {
            $mtime = filectime($filepath);
            $filename = $entry;
        }
    }
    $d->close();

    return $filepath;
}

// Be grateful. Cache results for a minute.
$cacheFile = $cacheDir . '/result_' . date('YmdHi') . '_' . sha1($url) . '.cache.html';
if (!file_exists($cacheFile)) {
    $html = request($url);
    file_put_contents($cacheFile, $html);
} else {
    $html = file_get_contents($cacheFile);
}

$dom = new DOMDocument();
@$dom->loadHtml($html); // @F.CK DOMDocument warnings.
$xpath = new DOMXpath($dom);
$rows = $xpath->query('//tr[@class="list-row  "]');

$list = [];
foreach ($rows as $row) {
    $objectUrl = $row->getElementsByTagName('a')[0]->attributes->getNamedItem('href')->value;
    $object = [
        'hash' => sha1($objectUrl), // unique object identifier
        'img' => $row->getElementsByTagName('img')[0]->attributes->getNamedItem('src')->value,
        'url' => $objectUrl,
        'addr' => cleanup($xpath->query('./td[@class="list-adress "]', $row)[0]->nodeValue),
        'price' => cleanup($xpath->query('.//*/span[@class="list-item-price"]', $row)[0]->nodeValue),
        'price_per_m' => cleanup($xpath->query('.//*/span[@class="price-pm"]', $row)[0]->nodeValue),
    ];

    $list[] = $object;
}

// Apply spam filter.
if ($spamFilter !== null) {
    $list = array_filter($list, function($object) use ($spamFilter) {
        // ... simply negate regular expression match
        return !preg_match($spamFilter, $object['url']);
    });
}

// Load results from previous check.
$previousResult = [];
if ($path = latestResult($resultDir)) {
    $previousResult = include $path;
}

// Extract new objects by comparing latest stored result and current response
$diff = array_udiff($list, $previousResult, function($a, $b) {
    return $a['hash'] == $b['hash'] ? 0 : -1;
});


if (empty($diff)) {
    echo "Nothing new\n";
    exit;
}

echo "News:\n";
print_r($diff);

// Save results for future comparisions
file_put_contents($resultDir . '/' . date('YmdHis') . '.php', "<?php\n return " . var_export($list, true) . ";");
