<?php

function cleanInput($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}


function validateInputs($arrayOfInputs, &$errors) {
    foreach ($arrayOfInputs as $key => $value) {
        switch ($key) {
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid Email. Email must be user@example.com.';
                } elseif (strlen($value) >= 50) {
                    $errors['email'] = 'Invalid Email. Email input must be less than or equal to 50 characters.';
                }
                break;
            case 'password':
                if (strlen($value) < 8) {
                    $errors['password'] = 'Invalid Password. Password input must be at least 8 characters.';
                }
                break;
            default:
                if (strlen($value) >= 80) {
                    $errors[$key] = "$key input must not be more than 80 characters.";
                } elseif (strlen($value) === 0) {
                    $errors[$key] = "$key input must not be empty.";
                }
                break;
        }
    }
    return $errors;
}

function hash_equalsFunc($known_string, $user_string) {
    $ret = 0;

    if (strlen($known_string) !== strlen($user_string)) {
        $user_string = $known_string;
        $ret = 1;
    }

    $res = $known_string ^ $user_string;

    for ($i = strlen($res) - 1; $i >= 0; --$i) {
        $ret |= ord($res[$i]);
    }

    return !$ret;
}

function logTofile($string) {
    $today_date = date("Y-m-d");
    $timestamp = date("Y-m-d H:i:s.");
    file_put_contents("/home/htmladmin/logs/bvnPortal/log-$today_date.log", "$timestamp ==>$string\n\n", FILE_APPEND);
}

function sendJsonResponse($status, $message, $data = null, $tableHeader = null) {
    header('Content-Type: application/json');

    $response = array(
        'status' => $status,
        'message' => $message,
    );

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($tableHeader != null) {
        $response['table_header'] = $tableHeader;
    }
    echo json_encode($response);
    exit();
}


/**
 * Custom hex2bin function for PHP 5.3 compatibility
 */
if (!function_exists('hex2bin')) {
    function hex2bin($str) {
        $sbin = "";
        $len = strlen($str);
        for ($i = 0; $i < $len; $i += 2) {
            $sbin .= pack("H*", substr($str, $i, 2));
        }
        return $sbin;
    }
}



?>


