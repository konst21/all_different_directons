<?php
/*
 * Solution of programm problem (link see below) with complicated conditions:
 * 'turn' and 'walk' can be placed in any order, e.g. turn 90 turn 50 walk 50 walk 30
 * Original problem:
 * https://open.kattis.com/problems/alldifferentdirections
 *
 * The decision is implemented "CPU module" - funtion cpu() (string :145, using in :116-:120 string),
 * the command system which consists of two instructions: the "turn" and "walk"
 * "CPU" cpu() works like a regular processor: the input is command and data,
 * processor performs calculations in accordance with the submitted command.
 *
 * Measurement of the CPU speed ( cpu_freq_measurement() ) give
 * around of 2 million instructions/sec
 *
 * Data in $data variable are left the first two groups of data from the source task to check the correctness of calculations
 * The last group (after the line "cpu") shows the operation of the processor when any combination of the commands "turn" and "walk"
 * Both measurements in this group should lead to a point with the coordinates (10,0)
 *
 * */

$data = <<<text
3
87.342 34.30 start 0 walk 10.0
2.6762 75.2811 start -45.0 walk 40 turn 40.0 walk 60
58.518 93.508 start 270 walk 50 turn 90 walk 40 turn 13 walk 5
2
30 40 start 90 walk 5
40 50 start 180 walk 10 turn 90 walk 5
cpu
0 0 turn 90 turn 90 turn 90 turn 90 walk 10
0 0 walk 5 walk 5 turn 45 turn 45 turn -10 turn -50 turn -30 walk 10 turn 90 turn 90 walk 2 walk 3 walk 5
text;

/**
 * main logic and output
 */
$string_array = string_split($data);
$groups = create_groups($string_array);
$out = [];
$counter = 0;
foreach ($groups as $g) {
    $points = points($g);
    $out[$counter] = [$points['avg']['x'], $points['avg']['y'], wrong_way($points)];
    $counter++;
}

//output
?><pre><?;//beauty will save the world
foreach ($out as $o) {
    echo implode(' ', $o) . '<br>'; //output results for data,
}

//CPU measurement
echo '<br>Main CPU data:<br>';
echo 'core: 1<br>';
$freq = cpu_freq_measurement(100000);
$freq = round($freq/1000)*1000;
echo "insructions per sec, rough: {$freq}<br>";


/**
 * Simpy split lines by EOL
 * @param $data
 * @return array
 */
function string_split ($data)
{
    return explode(PHP_EOL, $data);
}

/**
 * Create test groups as array of string
 * @param $string_array
 * @return array
 */
function create_groups ($string_array)
{
    $group_counter = -1;
    $out = [];

    foreach ($string_array as $data_string) {
        if (strlen($data_string) < 5) {
            $group_counter++;
        }
        else {
            $data_string = str_replace('start', 'turn', $data_string);
            $data_string = preg_replace('~\s{1,10}~', '|' ,trim($data_string));
            $out[$group_counter][] = trim($data_string);
        }
    }
    return $out;
}

/**
 * Main handler:
 * calculates group points coordinates and then average values
 * @param $group
 * @return array
 */
function points ($group)
{
    $x_avg = 0;
    $y_avg = 0;
    foreach ($group as $g) {
        $data_flow = explode('|', $g);
        //initial data
        $x = $data_flow[0];
        $y = $data_flow[1];
        $angle = 0;
        unset($data_flow[0]);
        unset($data_flow[1]);
        $data_flow = array_values($data_flow);

        $data_lengt = count($data_flow)/2;//each data item consists of two elements: command and data
        for ($i = 0; $i <= $data_lengt; $i++) {
            $command = $data_flow[$i*2];
            $data = $data_flow[$i*2+1];
            cpu($x, $y, $angle, $command, $data);//usage of main processor
        }
        $x_avg += $x;
        $y_avg += $y;
        $out[] = [
            'x' => $x,
            'y' => $y,
        ];
    }
    $walk_count = count($group);
    $avg = [
        'x' => round($x_avg/$walk_count ,2),
        'y' => round($y_avg/$walk_count, 2),
    ];
    $out['avg'] = $avg;
    return $out;
}

/**
 * Main CPU unit
 * @param $x
 * @param $y
 * @param $angle
 * @param $command
 * @param $data
 */
function cpu (&$x, &$y, &$angle, $command, $data)
{
    switch ($command) {
        case 'turn':
            $angle += $data;
            break;
        case 'walk':
            $angle_rad = deg2rad($angle);
            $x += $data*cos($angle_rad);
            $y += $data*sin($angle_rad);
            break;
        default:
    }
}

/**
 * Calculate distance between average point and each group point, and define wrong way ))
 * @param $points
 * @return mixed
 */
function wrong_way($points)
{
    $x_avg = $points['avg']['x'];
    $y_avg = $points['avg']['y'];
    unset($points['avg']);
    $distance = [];
    foreach ($points as $p) {
        $distance[] = round(sqrt(pow(($x_avg - $p['x']), 2) + pow(($y_avg - $p['y']), 2)), 2);
    }
    return max($distance);
}

/**
 * Calculate CPU performance: commands per sec
 * @param int $count
 * @return float|int
 */
function cpu_freq_measurement ($count = 10000)
{
    $flow = [];
    for ($i = 0; $i < $count; $i++)
    {
        $command = (mt_rand(0, 1) == 0)?'turn':'walk';
        $data = ($command == 'turn')?mt_rand(0,360):mt_rand(0,100);
        $flow[] = [
            'command' => $command,
            'data' => $data,
        ];
    }
    $x = 0;
    $y = 0;
    $angle = 0;
    $start = microtime(true);
    foreach ($flow as $f) {
        cpu($x, $y, $angle, $f['command'], $f['data']);
    }
    return $count / (microtime(true) - $start); //average "frequency"
}