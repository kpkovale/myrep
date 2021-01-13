# INFORMATION AND ASSUMPTIONS.

The following information is related to user_upload.php script (Script further in the text) and the assumptions which are necessary for its work.

## Information and requirements:
1. Operating System. The Script execution has been checked on both OS Windows 10 and Ubuntu 18.04.
2. PHP version. For successul exectution, PHP version 7.2 or above is required. (the same as in the task)
3. PHP Libraries. Postgres compatibility package for PHP is recommended:
  sudo apt-get install php7.2-pgsql
4. Database. The Postgres database ver 10.4 was used during testing and is recommended to be installed.
   However PostgreSQL ver 9.5 should also be sufficient.
5. Database. According to task conditions, only "create table" and "insert" operations are specified.
   Therefore at least one database on the host machine is reqiured.
6. Web-server. For successful PHP and Database interaction apache2 or nginx web-server is required.

### To install necessary components the below list of commands is recommended to be executed before the script if not done earlier:
- "sudo apt install php" - Enables php usage on current machine. You can use "sudo apt show php" to check the version link in your repository.
For latest version installation use "sudo apt update" or specify php version with "sudo apt install php7.2" command.
- "sudo apt-get install php7.2-pgsql" - Enables Postgres compatibility package for PHP
- "sudo apt-get install apache2" for Apache2 web-server or "sudo apt install nginx" for Nginx web-server
- "sudo apt install postgresql" - Allows to install PostgreSQL server on you local machine

## Assumptions for Command Line Directives:

*   --file [file name]

Current directive is required for every Script run except running with --help and --create_table directives.
File name can be written both as in square brackets (example: --file [users.csv]), as without them (example: --file users.csv)
In a CSV file ';' symbol as a delimeter between fields should be used.

*   --create_table

To create table "users" in the database.

If "--create_table" directive was given, the Script will terminate either after successul creation or after responce from the database that table with such name already exists, according to task conditions.

*   --dry_run
The dry run mode requires at least --file directive to be specified. Other directives are not necessary with --dry_run.

*   --help
The directive provides infromation for all acceptable commands for user_upload.php Script.

Following database user details (directives) are configurable:
*   -u
PostgreSQL username.

*   -p
PostgreSQL password.

*   -h
PostgreSQL host.
Can be used in a format: "host:port" (example: -h localhost:5432) or host only (example: localhost).

*   -n
PostgreSQL database name.

If any of -u/-p-/-n/-h parameters are not specified, the Script will request their input during the execution.
