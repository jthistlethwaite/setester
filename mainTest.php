<?php

require_once 'testcli/autoload.php';

use testcli\pingtest;
use testcli\speedtest;
use testcli\question;
use testcli\questionTypedAnswer;
use testcli\timer;


$bootsOutOfBox = new testcli\question("Did the device work out of the box?", ["Yes", "No"]);
$neededUnplugged = new testcli\question("Did the device have to be unplugged to factory reset it?", ["Yes", "No"]);
$unitWorks = new testcli\question("Does the unit function at all?", ["Yes", "No"]);
$bootTimer = new testcli\timer("Testing the time it takes the device to boot. Get ready to plug it in");

$continue = new question("Test another device?", ["Yes", "No"]);

$pingTest = new pingtest('google.com', 10);
$speedTest = new speedtest();

$testing = true;

$device = 1;

$batch = uniqid('setek-');

$outfile = 'output/'. $batch. ".csv";

$fh = fopen($outfile, "w");

$defaultRow = [
    "Device" => $device,
    "Batch" => $batch,
    "Boot Time" => null,
    "Unit Functions" => "No",
    "NeededReset" => "No",
    "ResetTime" => null,
    "Reset Worked" => "Not needed",

    "5ft_Speed_Down" => null,
    "5ft_Speed_Up" => null,
    "5ft_Ping_Average" => null,
    "5ft_Ping_PacketLoss" => null,

    "5m_Speed_Down" => null,
    "5m_Speed_Up" => null,
    "5m_Ping_Average" => null,
    "5m_Ping_PacketLoss" => null,

    "10m_Speed_Down" => null,
    "10m_Speed_Up" => null,
    "10m_Ping_Average" => null,
    "10m_Ping_PacketLoss" => null,
];

$columns = array_keys($defaultRow);

fputcsv($fh, $columns);

$x = 0;
while ($testing === true) {

    $answerRow = $defaultRow;
    $answerRow['Device'] = $device;

    $answerRow['Boot Time'] = $bootTimer->run();

    $answerRow['Unit Functions'] = $unitWorks->ask();


    $distances = ["5ft", "5m", "10m"];

    foreach ($distances as $distance) {
        do {
            $q = new question("Are you at the $distance mark?", ["Yes", "No"]);
            $a = $q->ask();
        } while ($a != "Yes");

        echo "Running ping test....";
        $ping = $pingTest->pingTarget('google.com');
        $answerRow[ "{$distance}_Ping_Average"] = $ping['latency']['average'];
        $answerRow[ "{$distance}_Ping_PacketLoss"] = $ping['packet_loss'];
        echo "OK\n";


        echo "Running speed test....";
        $speed = $speedTest->test();
        echo "OK\n";
        $answerRow[ "{$distance}_Speed_Down"] = $speed['download_mbps'];
        $answerRow[ "{$distance}_Speed_Up"] = $speed['upload_mbps'];
    }


    $device++;

    $x++;

    fputcsv($fh, $answerRow);

    if ($continue->ask() == 'Yes') {
        passthru("clear");
    } else {
        $testing = false;
    }

}

fclose($fh);


