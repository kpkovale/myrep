
<?php
echo "Let's begin!\n";

#var_dump($argv);

$shortopts = "u:p:h:";
$longopts = ["file:","create_table:","dry_run","help"];

$options = getopt($shortopts, $longopts);

/*--- INITIATING HELP MESSAGE ---*/
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
• --help – outputs the list of directives with details.
EOD;
if (is_null($options["help"])!= 1) exit(help_responce); // return help if parameter is specified

/* --- ASSIGNING DATABASE CONNECTION PARAMETERS TO VARIABLES --- */
if (is_null($options["u"])!= 1) $dbuser = $options["u"]; // parameter is specified
if (is_null($options["u"])!= 1) $dbpwd = $options["p"];  // parameter is specified
if (is_null($options["u"])!= 1) $dbhost = $options["h"]; // parameter is specified
if ($dbuser == null or $dbpwd == null or $dbhost == null) {
    exit("User exception: Database connection parameters are required. Please restart the script using correct parameters");
}

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


/*   --- if file parameter is handled on run --> reading file and validating data ---   */
if ($file_path != null) {
    /*   --- inserting file data into array ---   */
    $f_lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    for ($i = 0; $i < count($f_lines); $i++) {
        $file[$i] = explode(";", $f_lines[$i]);
    }
    /*  --- Setting name and surnames to start from Capital letter and lowering other letters ---   */
    for ($i = 0; $i < count($file); $i++) {
        for ($j = 0; $j < 2; $j++) {
            $file[$i][$j] = strtoupper(substr($file[$i][$j], 0, 1)) . strtolower(substr($file[$i][$j], 1));
        }
    }
    /*  --- lowering and validating e-mails --- */
    for ($i = 0; $i < count($file); $i++) {
        $file[$i][2] = strtolower($file[$i][2]);
        if (filter_var($file[$i][2], FILTER_VALIDATE_EMAIL) != false) $file[$i][3] = true; //e-mail is valid
        else $file[$i][3] = false; // e-mail is invalid
    }
}

/* --- CHECKING FOR THE DRY RUN OF THE SCRIPT --- */
if (is_null($options["dry_run"])!= 1) $is_dry_run = "ROLLBACK;"; else $is_dry_run = "COMMIT;";


/*  --- HERE GOES DATABASE ACTIONS BLOCK --- */

$dbconn = pg_connect("host=$dbhost port=5432 user=$dbuser password=$dbpwd")
   or die("Unable to establish database connection");
echo "Connected successfully: $dbconn ".pg_dbname($dbconn)."\n";

/*  --- SETTING COMMIT or ROLLBACK QUERY TO EXECUTE DEPENDING ON dry_run PARAMETER --- */
pg_prepare($dbconn, "run_commit", $is_dry_run);

/* --- query to check if new table already exists --- */
pg_prepare($dbconn, 'check_table_name',"SELECT table_name FROM information_schema.tables t WHERE t.table_name= $1");

/*  --- CHECKING WHETHER WE NEED TO CREATE NEW TABLE? --- */
if (is_null($options[ "create_table" ]) != 1) // parameter is specified
{
    $new_table_name = $options[ "create_table" ]; // putting table name into variable
    /* Forming a "create table" query */
    $q_create_table = "CREATE TABLE IF NOT EXISTS $new_table_name (
    id serial PRIMARY KEY NOT NULL,
    u_name VARCHAR(50) NOT NULL,
    u_surname VARCHAR(50) NOT NULL,
    email VARCHAR(350) UNIQUE NOT NULL);";
    /* Checking if table name is already busy */
    $t_exist = pg_execute($dbconn, "check_table_name", [$new_table_name]);

    if (!$t_exist) {
        pg_prepare($dbconn, "create_table", $q_create_table);
        pg_execute($dbconn, "create_table", []);
        pg_execute($dbconn, "run_commit", []);
        exit("New table has been created. Table name is $new_table_name. Terminating script");

        /* Memorizing table name in the "*.tmp" file. On the next run this table name will be used to insert data */
        file_put_contents("./write_table_name.tmp", $new_table_name);

    } else echo "Table with name " . pg_fetch_row(pg_execute($dbconn, "check_table_name", [$new_table_name]))[ 0 ] . " already exists";

}

/*  --- INSERTING DATA FROM FILE INTO DATABASE --- */



/*  --- Checking data output --- */
$result = pg_fetch_all(pg_query($dbconn, "SELECT u_name, u_surname, email FROM test_table"));
var_dump($result);
/*  --- --- --- --- --- --- --- */


pg_close($dbconn);
?>
