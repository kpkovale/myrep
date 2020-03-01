
<?php

$shortopts = "u:p:h:n:";
$longopts = ["file:","create_table:","dry_run","help"];

$options = getopt($shortopts, $longopts);

error_reporting(E_ERROR);
/*--- INITIATING HELP MESSAGE ---*/
const help_responce = <<<EOD
The following set of commands is specified for current PHP script:
• --file [csv file name]   – this is the name of the CSV-file to be parsed (REQUIRED)
• --create_table [table name]   – this will cause the PostgreSQL users table to be built (and no further action will be taken because of the conditions of the task)
• --dry_run   – this should be used with at least the --file directive in case of running the script without creating table / inserting data into the DB. All other functions will be executed, but the database won't be altered
• -u   – PostgreSQL username (REQUIRED if not dry_run)
• -p   – PostgreSQL password (REQUIRED if not dry_run)
• -h   – PostgreSQL host (REQUIRED if not dry_run). Can be used in a format: host:port or host only. If port is not specified, default port 5432 will be set.
• -n   – PostgreSQL database name. If not specified, default database will be used
• --help   – Outputs the list of directives with details.
EOD;
if (isset($options["help"])) exit(help_responce.PHP_EOL); // return help if parameter is specified

/* --- CHECKING FOR THE DRY RUN OF THE SCRIPT --- */
if (isset($options["dry_run"])) {
    $is_dry_run = true;
    echo "ATTENTION! Script is running in a DRY_RUN mode." . PHP_EOL;
} else $is_dry_run = false;

/* --- ASSIGNING DATABASE CONNECTION PARAMETERS TO VARIABLES --- */
if (isset($options["u"])) $dbuser = $options["u"]; // if user parameter is specified
if (isset($options["p"])) $dbpwd = $options["p"];  // if password parameter is specified
if (isset($options["h"])) $dbhost = $options["h"]; // if host parameter is specified
if (!$is_dry_run) {
    if ($dbuser == null or $dbpwd == null or $dbhost == null) {
        exit("User exception: Database connection parameters are required. Please restart the script using correct parameters");
    } else {
        /* Checking "host" format as "host:port" or "host" only */
        if (strpos($dbhost, ':') !== false) {
            $dbhp = explode(':', $dbhost);
            $dbhost = $dbhp[ 0 ];
            $dbport = $dbhp[ 1 ];
        } else $dbport = '5432';

        if (isset($options[ "n" ])) $dbname = $options[ "n" ]; // if database name parameter is specified
        else {
            $dbname = '';
            echo "DB NAME is not given. Using default." . PHP_EOL;
        }
    }
}

if (!isset($options["file"]) || empty($options["file"])) {
    exit("User exception: File path or file name is required. Please restart the script using correct parameters".PHP_EOL);
}

else {
    $file_path = $options["file"]; // if --file parameter is not empty
    if (strpos($file_path,'[') == 0 and strpos($file_path,']') == strlen($file_path) - 1) {
        $file_path = substr($file_path,1,strlen($file_path)-2);
    }
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
    $email_cnt = 0;
    for ($i = 0; $i < count($file); $i++) {
        $file[$i][2] = strtolower($file[$i][2]);
        if (filter_var($file[$i][2], FILTER_VALIDATE_EMAIL) != false) { //e-mail is valid
            $file[$i][3] = true;
            $email_cnt++;
        }
        else {  // e-mail is invalid
            $file[$i][3] = false;
            $email = $file[$i][2];
            echo "email: $email is invalid" . PHP_EOL;
        }
    }
    echo "Valid emails: $email_cnt | Invalid emails:". (count($file)-$email_cnt) . PHP_EOL;
}

if (!isset($options["create_table"]) && !file_exists("./write_table_name.tmp")) {
    echo "--create table directive is not specified. Also file with table name to insert data does not exist.
No further actions will be taken".PHP_EOL;
    exit();
}

/*  --- HERE GOES DATABASE ACTIONS BLOCK --- */
if ($is_dry_run) echo "DRY_RUN. No connection to the database will be made".PHP_EOL;
else {
    $dbconn = pg_connect("host=$dbhost port=$dbport user=$dbuser password=$dbpwd dbname=$dbname")
    or die("Unable to establish database connection");
    echo "Connected to database successfully: $dbconn. DB name: " . pg_dbname($dbconn) . "\n";

    /* --- query to check if new table already exists --- */
    pg_prepare($dbconn, 'check_table_name', "SELECT table_name FROM information_schema.tables t WHERE t.table_name= $1");
}

/*  --- CHECKING WHETHER WE NEED TO CREATE NEW TABLE? --- */
if (isset($options[ "create_table" ])) // parameter is specified
{
    $new_table_name = $options[ "create_table" ]; // putting table name into variable
    /* Forming a "create table" query */
    $q_create_table = "CREATE TABLE IF NOT EXISTS $new_table_name (
    id serial PRIMARY KEY NOT NULL,
    u_name VARCHAR(50) NOT NULL,
    u_surname VARCHAR(50) NOT NULL,
    email VARCHAR(350) UNIQUE NOT NULL);";

    /* Checking if table name is already busy */
    $t_exist = pg_fetch_row(pg_execute($dbconn, "check_table_name", [$new_table_name]))[ 0 ];
    #echo $t_exist;
    if ($is_dry_run) echo "DRY_RUN mode. No tables will be created." . PHP_EOL;
    else {
        if ($t_exist == null) {
            pg_query($dbconn, 'begin;');
            pg_prepare($dbconn, "create_table", $q_create_table);
            pg_execute($dbconn, "create_table", []);
            pg_query($dbconn, 'commit;');
            pg_close($dbconn);

            file_put_contents("./write_table_name.tmp", $new_table_name);
            exit("New table has been created. Table name is $new_table_name. Terminating script".PHP_EOL);

            /* Memorizing table name in the "*.tmp" file. On the next run this table name will be used to insert data */
        } else {
            echo "Table with name " . $t_exist . " already exists" . PHP_EOL;
            pg_close($dbconn);
            exit ('--create_table parameter is given. Task conditions require to terminate script execution.'.PHP_EOL);
        }
    }

}

/*  --- INSERTING DATA FROM FILE INTO DATABASE --- */

/* getting table name from file after previous --create_table run*/
$ins_table_name = file_get_contents("./write_table_name.tmp");

/*  --- CHECKING FOR DRY_RUN --- */
if (!$is_dry_run) {

    $t_exist = pg_fetch_row(pg_execute($dbconn, "check_table_name", [$ins_table_name]))[ 0 ];

    if ($t_exist == null) {
        echo "There is no table with $ins_table_name name in the database to insert data.
Please create table first using --create_table parameter" . PHP_EOL;
        exit();
    } else {
        $cnt_rec = 0;
        foreach ($file as $rec) {
            if ($rec[ 3 ] !== false) {
                $echeck = pg_fetch_all(pg_query($dbconn, "SELECT email FROM {$ins_table_name} where email = '{$rec[2]}'"));
                if ($echeck[ 0 ][ "email" ] != $rec[ 2 ]) {
                    pg_query($dbconn, "begin"); // STARTING TRANSACTION
                    $q_insert = "INSERT INTO $ins_table_name (u_name, u_surname, email) VALUES ($1, $2, $3)";
                    $exec_res = pg_query_params($dbconn, $q_insert, [$rec[ 0 ], $rec[ 1 ], $rec[ 2 ]]);
                    if ($exec_res !== false) {
                        $cnt_rec++;
                        pg_query($dbconn, 'commit');
                    } else {
                        pg_query($dbconn, 'rollback');
                        echo "Database has returned error: " . pg_last_error($dbconn) . PHP_EOL;
                    }
                    pg_query($dbconn, "end"); // FINISHING TRANSACTION
                } else echo "Record with e-mail: $rec[2] already exists." . PHP_EOL;

            }
        }

        echo "The script has been executed successfully. $cnt_rec records inserted into the database" . PHP_EOL;
    }
} else echo "The script has been executed successfully. $email_cnt valid emails has been prepared to insert into $ins_table_name table.
However, --dry_run is in active mode. Database was not altered." . PHP_EOL;

/*  --- Checking data output from the database --- */
#$result = pg_fetch_all(pg_query($dbconn, "SELECT u_name, u_surname, email FROM $ins_table_name"));
#var_dump($result);
/*  --- --- --- --- --- --- --- */

pg_close($dbconn);
?>
