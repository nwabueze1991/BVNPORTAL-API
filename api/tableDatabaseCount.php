<?php
header("Access-Control-Allow-Origin: *"); // Allow all domains
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../db/Database.php';
require_once(__DIR__ . '/../helper/helper.php');
require_once(__DIR__ . '/../helper/constant.php');

// Database connection parameters
// Function to execute a query and fetch results
global $conn;
$conn = new Database(DB_USER, DB_PASS, DB_SERVERNAME, DB_NAME);

function getQueryResult($sql) {
    global $conn;
    return $conn->getQueryResult($sql);
}

// Function to combine daily results
function combineDailyResult($others = array(), $nia = array()) {
    $result = array();
    foreach ($others as $other) {
        $date = $other["DAY"];
        $result[$date] = array(
            "BVN Check" => array("GLO" => $other["GLO_STAR_0"], "MTN" => $other["MTN_STAR_0"], "AIRTEL" => $other["AIRTEL_STAR_0"], "ETISALAT" => $other["ETISALAT_STAR_0"]),
            "BVN Validation" => array("GLO" => $other["GLO_STAR_1"], "MTN" => $other["MTN_STAR_1"], "AIRTEL" => $other["AIRTEL_STAR_1"], "ETISALAT" => $other["ETISALAT_STAR_1"]),
            "BVN Linking" => array("GLO" => $other["GLO_STAR_2"], "MTN" => $other["MTN_STAR_2"], "AIRTEL" => $other["AIRTEL_STAR_2"], "ETISALAT" => $other["ETISALAT_STAR_2"]),
            "NIA" => array("GLO" => 0, "MTN" => 0, "AIRTEL" => 0, "ETISALAT" => 0),
            "NIP" => array("GLO" => $other["GLO_STAR_5"], "MTN" => $other["MTN_STAR_5"], "AIRTEL" => $other["AIRTEL_STAR_5"], "ETISALAT" => $other["ETISALAT_STAR_5"])
        );
    }

    foreach ($nia as $nia_count) {
        $date = $nia_count["DAY"];
        if (!isset($result[$date]["NIA"])) {
            $result[$date]["NIA"] = array("GLO" => 0, "MTN" => 0, "AIRTEL" => 0, "ETISALAT" => 0);
        }
        $operator = $nia_count["OPERATOR"] == "9MOBILE" ? "ETISALAT" : $nia_count["OPERATOR"];
        $result[$date]["NIA"][$operator] = $nia_count["COUNT"];
    }
    logTofile("Combined query ===>" . print_r($result, true));

    return $result;
}

// Function to add new hourly results to existing results
function addToHourlyResult($current_result, $new_result) {
    foreach ($new_result as $other) {
        $temp = $other["TEMP"]; // Used for ordering
        $hour = $other["HOUR"];
        $date = $temp . "_" . $hour;
        if (!isset($current_result[$date])) {
            $current_result[$date] = array(
                "BVN Check" => array("GLO" => 0, "MTN" => 0, "AIRTEL" => 0, "ETISALAT" => 0),
                "BVN Validation" => array("GLO" => 0, "MTN" => 0, "AIRTEL" => 0, "ETISALAT" => 0),
                "BVN Linking" => array("GLO" => 0, "MTN" => 0, "AIRTEL" => 0, "ETISALAT" => 0),
                "NIA" => array("GLO" => 0, "MTN" => 0, "AIRTEL" => 0, "ETISALAT" => 0),
                "NIP" => array("GLO" => 0, "MTN" => 0, "AIRTEL" => 0, "ETISALAT" => 0)
            );
        }
        $operator = $other["OPERATOR"] == "9MOBILE" ? "ETISALAT" : $other["OPERATOR"];
        $current_result[$date][$other["CODE"]][$operator] = $other["COUNT"];
    }

    logTofile("addToHourlyResult  ===>" . print_r($current_result, true));

    return $current_result;
}

// Function to get daily report
function getDailyReport($start_date, $end_date) {
    $log_sql = <<<SQL
        SELECT 
            TO_CHAR(date_day, 'dd-mm-yyyy') day,
            mtn_star_0, glo_star_0, etisalat_star_0, airtel_star_0,
            mtn_star_1, glo_star_1, etisalat_star_1, airtel_star_1,
            mtn_star_2, glo_star_2, etisalat_star_2, airtel_star_2,
            mtn_star_5, glo_star_5, etisalat_star_5, airtel_star_5
        FROM bvn_count_stars
        WHERE 
            TO_DATE(TO_CHAR(date_day,'yyyy-mm-dd'),'yyyy-mm-dd') <= TO_DATE('$end_date','yyyy-mm-dd') AND
            TO_DATE(TO_CHAR(date_day,'yyyy-mm-dd'),'yyyy-mm-dd') >= TO_DATE('$start_date','yyyy-mm-dd')
        ORDER BY date_day DESC  
SQL;
    logTofile("first query  ===>" . $log_sql);

    $nia_sql = <<<SQL
        SELECT COUNT(*) count, TO_CHAR(createdon,'dd-mm-yyyy') day, UPPER(operator) operator 
        FROM ebillsv2_log 
        WHERE 
            biller = '11' AND
            TO_DATE(TO_CHAR(createdon,'yyyy-mm-dd'),'yyyy-mm-dd') <= TO_DATE('$end_date','yyyy-mm-dd') AND
            TO_DATE(TO_CHAR(createdon,'yyyy-mm-dd'),'yyyy-mm-dd') >= TO_DATE('$start_date','yyyy-mm-dd')
        GROUP BY TO_CHAR(createdon,'dd-mm-yyyy'), UPPER(operator)
        ORDER BY TO_DATE(TO_CHAR(createdon,'dd-mm-yyyy'),'dd-mm-yyyy') DESC   
SQL;
    logTofile("nia_sql query  ===>" . $log_sql);

    return combineDailyResult(getQueryResult($log_sql), getQueryResult($nia_sql));
}

// Function to get hourly report
function getHourlyReport($date) {
    $log_sql = <<<SQL
        SELECT COUNT(*) count, operator, code, hour, temp
        FROM (
            SELECT UPPER(operator) operator, 
                TO_CHAR(tstamp, 'HH12 AM') || ' - ' || TO_CHAR(tstamp + INTERVAL '1' HOUR, 'HH12 AM') hour,
                TO_CHAR(tstamp, 'HH24') temp,
                CASE 
                    WHEN (UPPER(operator) = 'MTN' AND USSD_CONTENT IN ('*565#', '*565*0#', '*565*0#;')) 
                         OR (UPPER(operator) = 'AIRTEL' AND SERVICE_CODE IN ('565*0', '565', '*565', '565', '565*', '565*0#', '*565*0#', '*565*0')) 
                         OR (UPPER(operator) = 'ETISALAT' AND UPPER(USSD_CONTENT) IN ('2A35363523', '2A3536352323', '2A3536352330', '2A3536352A3023', '2A3536352A30')) 
                        THEN 'BVN Check'
                    WHEN (UPPER(operator) = 'MTN' AND (USSD_CONTENT LIKE '%565*2*%' OR USSD_CONTENT LIKE '%565*2#')) 
                         OR SERVICE_CODE IN ('565*2', '*565*2', '*565*2#', '565*2#') 
                         OR SERVICE_CODE LIKE '%565*2*%' 
                         OR (UPPER(operator) = 'ETISALAT' AND UPPER(USSD_CONTENT) IN ('2A3536352A3223', '2A3536352A322A')) 
                        THEN 'BVN Linking'
                    WHEN (UPPER(operator) = 'MTN' AND (USSD_CONTENT LIKE '%565*1*%' OR USSD_CONTENT LIKE '%565*1#')) 
                         OR (UPPER(operator) = 'AIRTEL' AND (SERVICE_CODE IN ('565*1', '*565*1', '*565*1#', '565*1#') OR SERVICE_CODE LIKE '%565*1*%')) 
                         OR (UPPER(operator) = 'ETISALAT' AND UPPER(USSD_CONTENT) IN ('2A3536352A3123', '2A3536352A312A')) 
                        THEN 'BVN Validation'
                    WHEN (UPPER(operator) = 'MTN' AND (USSD_CONTENT LIKE '%565*5*%' OR USSD_CONTENT LIKE '%565*5#')) 
                         OR (UPPER(operator) = 'AIRTEL' AND (SERVICE_CODE IN ('565*5', '*565*5', '*565*5#', '565*5#') OR SERVICE_CODE LIKE '%565*5*%')) 
                         OR (UPPER(operator) = 'ETISALAT' AND UPPER(USSD_CONTENT) IN ('2A3536352A3523', '2A3536352A352A')) 
                        THEN 'NIP'
                END code
            FROM bvn_logs
            WHERE TO_DATE(TO_CHAR(tstamp, 'yyyy-mm-dd'), 'yyyy-mm-dd') = TO_DATE('$date', 'yyyy-mm-dd')
        )
        WHERE code IS NOT NULL
        GROUP BY code, operator, hour, temp
SQL;
    logTofile("getHourlyReport first query  ===>" . $log_sql);

    $glo_sql = <<<SQL
        SELECT COUNT(*) count, code, hour, temp, 'GLO' OPERATOR
        FROM (
            SELECT
                CASE
                    WHEN ussd_msg IN ('*565#', '*565*0', '*565*0#') THEN 'BVN Check'
                    WHEN ussd_msg LIKE '*565*2*%' OR ussd_msg = '*565*2#' THEN 'BVN Linking'
                    WHEN ussd_msg LIKE '*565*1*%' OR ussd_msg = '*565*1#' THEN 'BVN Validation'
                    WHEN ussd_msg LIKE '*565*5*%' OR ussd_msg = '*565*5#' THEN 'NIP'
                END code,
                TO_CHAR(tstamp, 'HH12 AM') || ' - ' || TO_CHAR(tstamp + INTERVAL '1' HOUR, 'HH12 AM') hour,
                TO_CHAR(tstamp, 'HH24') temp
            FROM glo_smpp_logs
            WHERE
                (ussd_msg IN ('*565#', '*565*0', '*565*0#') 
                 OR ussd_msg LIKE '*565*2*%' 
                 OR ussd_msg = '*565*2#' 
                 OR ussd_msg LIKE '*565*1*%' 
                 OR ussd_msg = '*565*1#' 
                 OR ussd_msg LIKE '*565*5*%' 
                 OR ussd_msg = '*565*5#')
                AND TO_DATE(TO_CHAR(tstamp, 'yyyy-mm-dd'), 'yyyy-mm-dd') = TO_DATE('$date', 'yyyy-mm-dd')
        )
        GROUP BY code, hour, temp   
SQL;
    logTofile("getHourlyReport glo_sql query  ===>" . $glo_sql);


    $connglo = new Database(DB_USER2, DB_PASS2, DB_SERVERNAME, DB_NAME);
    $glo = $connglo->getQueryResult($glo_sql);
    logTofile("getHourlyReport glo_sql result  ===>" . print_r($glo, true));


    $nia_sql = <<<SQL
        SELECT COUNT(*) count, operator, code, hour, temp
        FROM (
            SELECT 
                UPPER(operator) operator, 
                'NIA' code,
                TO_CHAR(createdon, 'HH24') temp,
                TO_CHAR(createdon, 'HH12 AM') || ' - ' || TO_CHAR(createdon + INTERVAL '1' HOUR, 'HH12 AM') hour
            FROM ebillsv2_log 
            WHERE 
                biller = '11' AND
                TO_DATE(TO_CHAR(createdon, 'yyyy-mm-dd'), 'yyyy-mm-dd') = TO_DATE('$date', 'yyyy-mm-dd')
        )
        GROUP BY code, operator, hour, temp   
SQL;
    logTofile("getHourlyReport nia_sql result  ===>" . $nia_sql);

    $hourly_report = addToHourlyResult(array(), getQueryResult($log_sql));
    $hourly_report = addToHourlyResult($hourly_report, $glo);
    $combined_report = addToHourlyResult($hourly_report, getQueryResult($nia_sql));
    krsort($combined_report);
    return $combined_report;
}

$table_header = "Hit count for today.";
$hourly = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dateRange'])) {
        $date_range = explode(" - ", $_POST['dateRange']);
        $start_date = $date_range[0];
        $end_date = $date_range[1];

        if ($start_date && $end_date) {
            if ($start_date != $end_date) {
                $report = getDailyReport($start_date, $end_date);
                $hourly = false;
                $table_header = "Hit count from $start_date to $end_date";
            } else {
                $report = getHourlyReport($start_date);
                $hourly = true;
                $table_header = "Hit count for $start_date";
            }
        } else {
            sendJsonResponse(400, 'Invalid date range');
        }
    } else {
        $start_date = $end_date = $today = date("Y-m-d");
        $table_header = "Hit count for today";
        $report = getHourlyReport($today);
    }
} else {
    sendJsonResponse(405, 'Method Not Allowed. Use POST.');
}



sendJsonResponse(200, $hourly, $report, $table_header);
?>
