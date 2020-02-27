
<?php
echo "Let's begin!\n";

#var_dump($argv);

$shortopts = "u:p:h:";
$longopts = ["file:","create_table:","dry_run","help"];

#var_dump($shortopts);
#var_dump($longopts);
$options = getopt($shortopts, $longopts);
#var_dump($options);

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

/*  MAY NEED THIS LATER  */
#using STDOUT on incorrect data responce
#echo "my message" . PHP_EOL;
#using STDERR
#fwrite(STDERR, "hello, world!" . PHP_EOL);
$f_lines = file('.//test_file.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
for ($i = 0; $i < count($f_lines); $i++)  {
    $file[$i] = explode(";", $f_lines[$i] );
}

#$file = file_get_contents('.//test_file.csv', FILE_USE_INCLUDE_PATH);
var_dump($file);

?>
