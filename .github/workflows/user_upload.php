
<?php
echo "Let's begin!\n";

#var_dump($argv);

$shortopts = "u:p:h:";
$longopts = ["file:","create_table:","dry_run","help"];

$options = getopt($shortopts, $longopts);

const help_responce = <<<EOD
The following set of commands is specified for current PHP script:
• --file [csv file name] – this is the name of the CSV to be parsed
• --create_table – this will cause the PostgreSQL users table to be built (and no further action
    will be taken)
• --dry_run – this will be used with the --file directive in case we want to run the script but not
    insert into the DB. All other functions will be executed, but the database won't be altered
• -u – PostgreSQL username
• -p – PostgreSQL password
• -h – PostgreSQL host
• --help – which will output the above list of directives with details.
EOD;
if (is_null($options["help"])!= 1) echo help_responce;

#var_dump($options);

if (is_null($options["u"])!= 1) $dbuser = $options["u"];
if (is_null($options["u"])!= 1) $dbpwd = $options["p"];
if (is_null($options["u"])!= 1) $dbhost = $options["h"];

if ($dbuser == null or $dbpwd == null or $dbhost == null) {
    exit("User exception: Database connection parameters required. Please restart the script using correct parameters");
}

#echo "DBUser: ".$dbuser." DBPassword: ".$dbpwd." DBHost: ".$dbhost."\n";
/*  --- MAY NEED THIS LATER ---  */
#using STDOUT on incorrect data responce
#echo "my message" . PHP_EOL;
#using STDERR
#fwrite(STDERR, "hello, world!" . PHP_EOL);
/*-----*/

if (is_null($options["file"])!= 1) {
    $file_path = $options["file"]; // if --file parameter is not empty
    if (strpos($file_path,'[') == 0 and strpos($file_path,']') == strlen($file_path) - 1) {
        $file_path = substr($file_path,1,strlen($file_path)-2);
    }
}

if ($file_path == null) {
    exit("User exception: File path or file name is required. Please restart the script using correct parameters");
}

 #   substr($options["file"],1,strlen($options["file"])-2);
#echo "test path: ".$file_path."\n";
#$file_path = './/test_file.csv';
/*   --- if file parameter is handled on run --> reading file and validating data ---   */
if ($file_path != null) {
/*   --- inserting file data into array ---   */
$f_lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
for ($i = 0; $i < count($f_lines); $i++)  {
    $file[$i] = explode(";", $f_lines[$i] );
}
/*  --- Setting name and surnames to start from Capital letter and lowering other letters ---   */
for ($i = 0; $i < count($file); $i++) {
    for ($j = 0; $j < 2; $j++) {
        $file[$i][$j] = strtoupper(substr($file[$i][$j],0,1)).strtolower(substr($file[$i][$j],1));
    }
}
/*  --- lowering and validating e-mails --- */
for ($i = 0; $i < count($file); $i++) {
    $file[$i][2] = strtolower($file[$i][2]);
    if (filter_var($file[$i][2], FILTER_VALIDATE_EMAIL) != false) $file[$i][3] = true; //e-mail is valid
    else $file[$i][3] = false; // e-mail is invalid
}
#var_dump($file);
}


?>
