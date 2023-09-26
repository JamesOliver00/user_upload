<?php


$short_options = "";
$short_options .= "u:"; // db username
$short_options .= "p:"; // db password
$short_options .= "h:"; // db host

$long_options = 
[
    "file:",        // [csv file name]
    "create_table", // creates 'users' table, then exits
    "dry_run",      // used with --file,run as normal except for db insert
    "help",         // output options with details
];

// prints options and details then exits
function help(){

}


function main($args){

    $username = "";
    $pass = "";
    $host = "";
    $host_ip = "";
    $host_port = "";
    $filename = "";

    $create_table = false;
    $dry_run = false;

    //process args
    foreach($args as $arg => $val){
        switch ($arg){
            case "u":
                $username = $val;
                break;
            case "p":
                $pass = $val;
                break;
            case "h":
                if (preg_match('/^(\d[\d.]+):(\d+)\b/', $val, $matches)) {
                    $host_ip = $matches[1];
                    $host_port = $matches[2];
                }
                $host = $val;
                break;
            case "file":
                $filename = $val;
                break;
            case "create_table":
                $create_table = true;
                break;
            case "dry_run":
                $dry_run = true;
                break;
            case "help":
                help();
                break;
            default : 
                break;
        }
        
    }

}

$args = getopt($short_options, $long_options);
main($args);

exit();