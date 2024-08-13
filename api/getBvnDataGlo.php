<?php
// Include Helper Files
require_once(__DIR__ . '/../helper/helper.php');
require_once(__DIR__ . '/../helper/constant.php');
//include 'session.php';
/*
 * // Include Helper Files
require_once(__DIR__ . '/../helper/helper.php');
require_once(__DIR__ . '/../helper/constant.php');
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
//$table = `(select source_address "MSISDN", SHORT_MESSAGE "SERVICE_CODE",SHORT_MESSAGE "USSD_CONTENT", TIME_IN "TSTAMP", 'GLO' "OPERATOR" from SMREQUEST_IN2)`;
$table = "SMREQUEST_IN2";
// Table's primary key
$primaryKey = 'id';

// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes

$columns = array(
    array( 'db' => 'SOURCE_ADDRESS',   'dt' => 0 ),
    array( 'db' => 'SHORT_MESSAGE', 'dt' => 1),
    array( 'db' => 'TIME_IN',  'dt' => 2, 'formatter'=>function( $d, $row ) {
            $format = "d-M-y h.i.s.u A";
            $timezone = new DateTimeZone('Africa/Lagos');
            $date = DateTime::createFromFormat($format, $d, $timezone);
            return $date->format('Y-m-d H:i:s');
        } )
);


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 */
require(__DIR__ . '/../library/bvnSSP.php');
$dataResult = SSP::simple($_GET, $table, $primaryKey, $columns);
logTofile("DataTable Result ===>".print_r($dataResult,true));
if ($dataResult) {
    sendJsonResponse(200, "success", $dataResult);
} else {
    sendJsonResponse(400, "failed");
}






?>
