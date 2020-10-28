<?php

require_once 'testcli/autoload.php';

use testcli\pingtest;
use testcli\speedtest;
use testcli\question;
use testcli\questionTypedAnswer;
use testcli\timer;
use testcli\System\Console;


$bootsOutOfBox = new question("Did the device work out of the box?", ["Yes", "No"]);
$neededUnplugged = new question("Did the device have to be unplugged to factory reset it?", ["Yes", "No"]);
$unitWorks = new question("Does the unit function at all?", ["Yes", "No"]);
$bootTimer = new timer("Testing the time it takes the device to boot. Get ready to plug it in");

$resetRequiredUnplug = new question("Did you need to unplug the extender to reset it?", ["Yes", "No"]);


$resetTimer = new timer("Time how long the reset process takes. Get ready to begin.");

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
    "Reset Required Unplug" => null,

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

    "Test Time" => 0
];

echo <<<EOQ
#################################################
Wifi device profiling tool
#################################################

During this test you will open a wifi extender and perform a series of tests.

This tool will document the results in a spreadsheet you can use to generate
reports about the results.

This batch will be saved in $outfile.\n\n
EOQ;

$fullStart = microtime(true);

$name = new questionTypedAnswer("Please enter your name: ");
$techName = $name->ask();

$model = new questionTypedAnswer("Please enter the model number you are testing: ");
$testModel = $model->ask();

fputcsv($fh, ["Technician:", $techName]);
fputcsv($fh, ["Model:", $testModel]);
fputcsv($fh, ["Test Date:", date("Y-m-d H:i:s")]);
fwrite($fh, "\n");
$columns = array_keys($defaultRow);

fputcsv($fh, $columns);






while ($testing === true) {

    $loopStart = microtime(true);

    $answerRow = $defaultRow;
    $answerRow['Device'] = $device;

    $answerRow['Boot Time'] = $bootTimer->run();

    $answerRow['Unit Functions'] = $unitWorks->ask();

    if ($answerRow['Unit Functions'] == "No") {

        Console::drawBreak();

        echo "You will need to follow the device reset procedure and try again. You will time how long the reset process takes.";

        $answerRow['ResetTime'] = $resetTimer->run();

        $answerRow['NeededReset'] = 'Yes';

        $answerRow['Reset Worked'] = $unitWorks->ask();

        $answerRow['Reset Required Unplug'] = $resetRequiredUnplug->ask();

        if ($answerRow['Reset Worked'] == 'No') {

            $device++;

            $answerRow['Test Time'] = microtime(true) - $loopStart;

            fputcsv($fh, $answerRow);

            if ($continue->ask() == 'Yes') {
                Console::clear();
                continue;
            } else {
                $testing = false;
                break;
            }
        }

    }


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

    $answerRow['Test Time'] = microtime(true) - $loopStart;

    fputcsv($fh, $answerRow);

    if ($continue->ask() == 'Yes') {
        Console::clear();
    } else {
        $testing = false;
    }

}

$fullStop = microtime(true);

$statfile = $outfile. ".stats.txt";

file_put_contents($statfile, "Total time: ". ($fullStop - $fullStart). " seconds\n");

fclose($fh);


