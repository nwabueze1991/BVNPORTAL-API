
<?php
header("Access-Control-Allow-Origin: *"); // Allow all domains
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Your existing PHP code



require_once(__DIR__ . '/../helper/helper.php');
/*
 * DataTables example server-side processing script.
 *
 * Please note that this script is intentionally extremely simply to show how
 * server-side processing can be implemented, and probably shouldn't be used as
 * the basis for a large complex system. It is suitable for simple use cases as
 * for learning.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */

// DB table to use
$table = 'ebillsv2_log';

// Table's primary key
$primaryKey = 'LOGSID';

// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes

$columns = array(
    array('db' => 'MSISDN', 'dt' => 0),
    array('db' => 'USSDCODE', 'dt' => 1),
    array('db' => 'CREATEDON', 'dt' => 2, 'formatter' => function( $d, $row ) {
            $format = "d-M-y h.i.s.u A";
            $timezone = new DateTimeZone('Africa/Lagos');
            $date = DateTime::createFromFormat($format, $d, $timezone);
            return $date->format('Y-m-d H:i:s');
        }),
    array('db' => 'OPERATOR', 'dt' => 3, 'formatter' => function( $d, $row ) {
            return strtoupper($row['OPERATOR']);
        })
);

   
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 */
require(__DIR__ . '/../library/bvnSSPNia.php');
$data = SSP::simple($_GET, $table, $primaryKey, $columns);
logTofile("REQUEST." . json_encode($_GET));
logTofile("bvnSSPNia".print_r($data,true));
echo json_encode($data);

?>
