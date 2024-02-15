<?php

$f = json_decode(file_get_contents("schema.json"), true);
$results = [];
foreach ($f as $entry) {
    parseEntry($entry, $results);
}


function parseEntry($e, &$results, $parent = null) {
    $retarr = [];
    print $e['path']."\n";
    print json_encode($e['info'])."\n";
}