<?php

$shortopts = "u:p:h:n:";
$longopts = ["file:","create_table","dry_run","help"];

$options = getopt($shortopts, $longopts);

error_reporting(E_ERROR);

/*checking user's mistakes in commands syntax*/
function check_options($shortopts, $longopts) {
  $argv = $_SERVER['argv'];
  $argc = $_SERVER['argc'];
  $args_list = $argv; //recieving overall list of arguments
  unset($args_list[0]); //remove file name from the arguments list
  $optind = null;

  $options = getopt($shortopts, $longopts, $optind);
  /**/
  if ($optind != $argc) {
    $err_msg =
"An error ocured on \"".($optind+1)."\" argument out of \"$argc\".
The argument \"".$argv[$optind]."\" does not correspond to any available command.
Use --help to check the list of available commands.".PHP_EOL;

    return [False, $err_msg];
  }

  foreach ($args_list as $given_arg) {
    $end_cicle = 0;
    for ($i=1; $i <= count($options) ; $i++) {
      if ($end_cicle === 1) break;
      foreach ($options as $option_key => $option_value) {
        if ($given_arg === "-".$option_key || $given_arg === "--".$option_key) {
          $end_cicle = 1;
          break;
      }
        elseif ($given_arg === $option_value) {
          $end_cicle = 1;
          break;
      }
        elseif ($given_arg === "-".$option_key."=".$option_value) {
          $end_cicle = 1;
          break;
      }
    }
      if ($i == count($options) && $end_cicle == 0) {
      $err_msg =
"The argument \"".$given_arg."\" does not correspond to any available command.
Use --help to check the list of available commands.".PHP_EOL;
      return [False, $err_msg];
      }
    }
  }
  return [True, '']; // return True if no mistakes found
}

[$check_result, $err_msg] = check_options($shortopts, $longopts);
if (!$check_result) {
  echo $err_msg.PHP_EOL;
  exit();
}

/*--- INITIATING HELP MESSAGE ---*/
const help_responce = <<<EOD
user_upload.php [-u=<...> & -p=<...> & -h=<...> [& -n=<...>]]
                [--create_table] [--file=<...>] [--dry_run] [--help]
The following set of commands is specified for current PHP script.
To define commands value both space or equality delimiters are fine.
Example: < -u username > | < -u=username >
• --file [csv file name] – this is the name of the CSV to be parsed
• --create_table – this will cause the PostgreSQL "users" table to be built
  (and no further action will be taken because of the conditions of the task)
• --dry_run – this will be used with the --file directive in case we want
  to run the script but not insert into the DB.
  All other functions will be executed, but the database won't be altered
• -u [username] – PostgreSQL username
• -p [password] – PostgreSQL password
• -h [host:port] – PostgreSQL host in a format: host:port.
  If the port is not specified, default port 5432 will be set.
• -n [DBName] – PostgreSQL database name. If not specified, "username" database will be used
• --help – outputs the list of directives with details.
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
        exit("Script defined exception: Database connection parameters are required. Please restart the script using correct parameters");
    } else {
        /* Checking "host" format as "host:port" or "host" only */
        if (strpos($dbhost, ':') !== false) {
            $dbhp = explode(':', $dbhost);
            $dbhost = $dbhp[ 0 ];
            $dbport = $dbhp[ 1 ];
        } else $dbport = '5432';

        if (isset($options[ "n" ])) $dbname = $options[ "n" ]; // if database name parameter is specified
        else {
            $dbname = $dbuser;
            echo "DB NAME is not given. Using \"$dbuser\" database name to connect." . PHP_EOL;
        }
    }
}
If (!isset($options[ "create_table" ])) {
  if (!isset($options["file"]) || empty($options["file"])) {
      exit("Script defined exception: File path or file name is required. Please restart the script using correct parameters".PHP_EOL);
  }

  else {
      $file_path = $options["file"]; // if --file parameter is not empty
      if (strpos($file_path,'[') == 0 and strpos($file_path,']') == strlen($file_path) - 1) {
          $file_path = substr($file_path,1,strlen($file_path)-2);
      }
  }
}



/*   --- if file parameter is handled on run --> reading file and validating data ---   */
if ($file_path != null) {
    /*   --- inserting file data into array ---   */
    $f_lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($f_lines === false) {
      exit("file \"$file_path\" is empty. No records were found. Please try another file.\n");
    }

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

// if (!isset($options["create_table"]) && !file_exists("./write_table_name.tmp")) {
//     echo "--create table parameter is not specified.
// File with table name to insert data does not exist".PHP_EOL;
//     exit();
// }

$connection_string = "host=$dbhost port=$dbport user=$dbuser password=$dbpwd dbname=$dbname";


/*  --- HERE GOES DATABASE ACTIONS BLOCK --- */
if ($is_dry_run) echo "DRY_RUN. No connection to the database will be made".PHP_EOL;
else {
    $dbconn = pg_connect($connection_string)
    or die("Database has returned error: $connection_string
    \"". error_get_last()['message']."\"
    Unable to establish database connection\n");
    // echo pg_result_error(pg_connect($connection_string,PGSQL_CONNECT_FORCE_NEW))."\n";
    echo "Connected to database successfully: $dbconn. DB name: " . pg_dbname($dbconn) . "\n";
    // try {
    //   $dbconn = pg_connect($connection_string);
    // } catch (\Exception $e) {
    //   echo $e->getMessage;
    //   exit();
    // }



    /* --- query to check if new table already exists --- */
    pg_prepare($dbconn, 'check_table_name', "SELECT table_name FROM information_schema.tables t WHERE t.table_name= $1");
}

/*  --- CHECKING WHETHER WE NEED TO CREATE NEW TABLE? --- */
if (isset($options[ "create_table" ])) // parameter is specified
{
    $new_table_name = "users"; // putting table name into variable
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

            pg_prepare($dbconn, "create_table", $q_create_table);
            pg_execute($dbconn, "create_table", []);
            pg_query($dbconn, 'commit;');
            pg_close($dbconn);

            //file_put_contents("./write_table_name.tmp", $new_table_name);
            exit("New table has been created. Table name is \"$new_table_name\".".PHP_EOL);

            /* Memorizing table name in the "*.tmp" file. On the next run this table name will be used to insert data */
        } else {
            echo "Table with name " . $t_exist . " already exists" . PHP_EOL;
            pg_close($dbconn);
            exit ('--create_table parameter was given. Task conditions require to terminate script execution.'.PHP_EOL);
        }
    }

}

/*  --- INSERTING DATA FROM FILE INTO DATABASE --- */

/* getting table name from file after previous --create_table run*/
$ins_table_name = "users";

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
