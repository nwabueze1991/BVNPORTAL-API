<?php
/**
 *
 */
 class Database{
   private $servername;
   private $username;
   private $password;
   private $database;
   function __construct()
   {
     $this->servername = "192.164.177.171:1527";
     $this->username = 'bvn';
     $this->password = 'bvn123';
     $this->database = 'mydb2';
     $this->conn = oci_connect($this->username,$this->password,"//$this->servername/$this->database:POOLED");
   }
 }


 ?>