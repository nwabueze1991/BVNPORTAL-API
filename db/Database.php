<?php

class Database {

    private $conn;

    public function __construct($username, $password, $servername, $dbname) {
        $this->conn = oci_connect($username, $password, $servername . '/' . $dbname);

        if (!$this->conn) {
            $e = oci_error();
            throw new Exception('Could not connect to the database: ' . $e['message']);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function executeQuery($sql, $params) {
        $conn = $this->getConnection();

        // Prepare the SQL statement
        $stmt = oci_parse($conn, $sql);
        if (!$stmt) {
            $e = oci_error($conn);
            throw new Exception('Failed to parse SQL: ' . $e['message']);
        }

        // Bind parameters
        foreach ($params as $key => &$value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        // Execute the query
        $result = oci_execute($stmt);
        if (!$result) {
            $e = oci_error($stmt);
            throw new Exception('Failed to execute query: ' . $e['message']);
        }

        // Fetch all results
        $rows = array();
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $row;
        }

        // Free the statement and close the connection
        oci_free_statement($stmt);

        return $rows;
    }

    public function getQueryResult($sql) {
        $query = oci_parse($this->conn, $sql);
        oci_execute($query);
        oci_fetch_all($query, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        return $result;
    }

    public function __destruct() {
        oci_close($this->conn);
    }

    public function sqlExec($bindings = array(), $sql = null) {
        if ($sql === null) {
            $sql = $bindings;
            $bindings = array();
        }

        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            $e = oci_error($this->conn);
            die("Error parsing SQL: " . $e['message']);
        }

        // Bind parameters
        if (is_array($bindings) && !empty($bindings)) {
            foreach ($bindings as $binding) {
                if (isset($binding['key']) && isset($binding['val'])) {
                    $result = @oci_bind_by_name($stmt, $binding['key'], $binding['val']);
                    if (!$result) {
                        $e = oci_error($this->conn);
                        die("Error binding parameter: " . $e['message']);
                    }
                } else {
                    die("Invalid binding forma " . $e['message']);
                }
            }
        }

        // Execute the statement
        $result = @oci_execute($stmt);
        if (!$result) {
            $e = oci_error($this->conn);
            die("Error executing SQL " . $e['message']);
        }

        $res = array();
        oci_fetch_all($stmt, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW);
        oci_free_statement($stmt);

        return $res;
    }

}

?>
