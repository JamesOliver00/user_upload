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


// sends output to file handle
function f_output($output_stream, $s){
    fwrite($output_stream, "\r\n\r\n".$s."\r\n\r\n");
    return 0;
}

// prints options and details then exits
function help(){
    $directives = 
    [
        "-u" => "username of the MYSQL user",
        "-p" => "password of the MYSQL user",
        "-h" => "ip of host database. Port can be specified by attaching the port number to the end of the ip seperated by a ':'. Example '<ip>:<port>' ",
        "--file" => "this is the name of the csv to be parsed",
        "--create_table" => "this will create a 'users' in the database defined by host -h, then the script will exit",
        "--dry_run" => "No inserts will be made to the database defined by host -h and the script will execute as normal",
        "--help" => "Prints possible command line directive and corresponding functions",
    ];
    
    
    $help = "[===== Help =====]\r\n\r\n";
    foreach($directives as $directive => $desc){
        $help .= "$directive - $desc\r\n";
    }
    f_output(STDOUT, $help);
    exit();
}


function main($args){

    $username = "";
    $pass = "";
    $host = "";
    $host_ip = "";
    $host_port = "";
    $filename = "";
    $dbname = 'test';
    $tablename = "users";

    $create_table = false;
    $dry_run = false;

    //process args
    if(!count($args)){
        $message = "Error : No accepted options indetified";
        $message .= "\r\n\r\n"; 
        $message .= "Try... \r\n \"php user_upload.php --help\" \r\n to see available options";

        f_output(STDOUT, $message);
        return;
    }

    foreach($args as $arg => $val){
        switch ($arg){
            case "u":
                $username = $val;
                break;
            case "p":
                $pass = $val;
                break;
            case "h":
                if (preg_match('/^([\.a-zA-Z0-9]+):(\d+)\b/', $val, $matches)) {
                    
                    $host_ip = $matches[1];
                    $host_port = $matches[2];
                }
                else{
                    $host_ip = $val;
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
    //check dry run
    if(!$dry_run){
        // connect to dbE
        $dsn = "mysql:host=" . $host_ip;
        if($host_port!=''){
            $dsn .= ";port=" . $host_port;
        }

        try{
            $pdo = new PDO($dsn, $username, $pass);
        }
        catch(Exception $e){
            $err = $e -> getMessage()."\n";
            $message = "Error: Database connection fail\r\n";
            $message .= "Message: $err";
            f_output(STDOUT, $message);
            exit();
        }
        
        //create table
        if($create_table)
        {
            $createDBQ = "CREATE DATABASE IF NOT EXISTS $dbname";
            $pdo->query($createDBQ);
            $pdo->query("USE test");
            $createTableQ = "CREATE TABLE IF NOT EXISTS $tablename (
                name varchar(255),
                surname varchar(255),
                email varchar(255) UNIQUE
            );";
            $pdo->query($createTableQ);
            exit();
        }
        else{
            try{
                $pdo->query("USE $dbname");
                $queryRes = $pdo->query("SHOW TABLES FROM $dbname LIKE '$tablename'");
                $rows = $queryRes->fetchAll();
                if(!count($rows)){
                    throw new Exception("Error: Table $tablename doesn't exist");
                }
            }
            catch (Exception $e){
                $message = "Error: Database not figured correctly\r\n";
                $message .= "Try, --create_table to resolve database issues";
                f_output(STDOUT, $message);
                exit();
            }
        }
    }

    //array of data to be inserted into db
    $valid_data = [];

    $filepath = __DIR__. '/' . $filename; 
    //process csv file
    $fh = fopen($filepath, 'r' );
    if($fh){
        $firstRow = true;
        while(($data = fgetcsv($fh)) !== FALSE){
            if($firstRow){
                $firstRow = false;
                continue;
            }
            [$firstname, $lastname, $email] = $data;
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            if(filter_var($email, FILTER_VALIDATE_EMAIL)){
                $email = strtolower($email);

                $firstname = trim($firstname, "\n\r\t ");
                $lastname = trim($lastname, "\n\r\t ");
                $firstname = str_replace('\' ', '\'', ucwords(str_replace('\'', '\' ', strtolower($firstname))));
                $lastname = str_replace('\' ', '\'', ucwords(str_replace('\'', '\' ', strtolower($lastname))));

                $valid_data[]= [$firstname, $lastname, $email];
            }
            else{
                $message = "Error: $email is not a valid email format";
                f_output(STDOUT, $message);
            }
        }
    }
    else
    {
        $message = "Error: File doesn't exist\r\n";
        f_output(STDOUT, $message);
        exit();
    }
    
    //check dry run
    if(!$dry_run){
        //insert into db
        foreach($valid_data as $i => $data){
            [$firstname, $lastname, $email] = $data;
            $ins = "INSERT INTO users (name, surname, email) VALUES (? , ? , ? )";
            $insStmt = $pdo->prepare($ins);
            try{
                $insStmt -> execute([$firstname, $lastname, $email]);
            }
            catch(Exception $e){
                f_output(STDOUT, "Error: " . $e->getMessage() . "... skipping this email ...");
            }
        }
    }
}

$args = getopt($short_options, $long_options);
main($args);

exit();