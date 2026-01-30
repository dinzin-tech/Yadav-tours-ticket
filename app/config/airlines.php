<?php

$airlines_json = json_decode(file_get_contents(__DIR__ . '/airlines.json'));

// map airline codes to names
$airlines = [];

foreach ($airlines_json as $airline) {
    if (isset($airline->id)) {
        $airlines[$airline->id] = $airline;
    }
}

return $airlines ?? [];