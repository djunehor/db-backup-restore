<?php
namespace Djunehor\DB;
/**
 * @author djunehor
 * @copyright  Copyright (c) 2019 djunehor.com
 * @link  http://github.com/db-backup-restore
 * @version 1.0
 *
 *----------------------------------------------------------------------
 */
class BackUp {
    var $db; // 
    var $database; // 
    var $sqldir; //
	var $lang = [];
    
    private $ds = "\n";
    
    public $sqlContent = "";
    
    public $sqlEnd = ";";

	/**
	 *
	 *
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param string $charset
	 * @param string $lang
	 */
    function __construct($host = 'localhost', $username = 'root', $password = '', $database = 'test', $charset = 'utf-8', $lang = 'en') {
	    $this->setLang($lang);
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->charset = $charset;
        set_time_limit(0);
        
        $this->conn  = mysqli_connect ( $this->host, $this->username, $this->password, $this->database ) or die( $this->lang['db_conn_error'].' : '.mysqli_connect_errno());
        
        mysqli_query ( $this->conn , 'SET NAMES ' . $this->charset, $this->db );

    }

    public function setLang($lang = null) {
    	if($lang and file_exists(__DIR__.'/lang/'.$lang."/messages.php")) {
    		$folder = __DIR__.'/lang/'.$lang."/messages.php";

	    } else if (file_exists(__DIR__.'/lang/'.$lang."/messages.php")) {
    		$folder = __DIR__.'/lang/'.$lang."/messages.php";
	    } else {
    		die('No Localization file found!');
	    }

	    $this->lang = include($folder);
    }

    /*
     *
     */
    function getTables() {
        $res = mysqli_query ($this->conn , "SHOW TABLES" );
        $tables = array ();
        while ( $row = mysqli_fetch_array ( $res ) ) {
            $tables [] = $row [0];
        }
        return $tables;
    }

    /*
     *
     * ------------------------------------------start----------------------------------------------------------
     */

	/**
	 *
	 *
	 * @param string $tablename
	 * @param $dir
	 * @param int $size
	 *
	 * @return bool
	 */
    function backup($tablename = null, $dir = null, $size = null) {
        $dir = $dir ? $dir : __DIR__.'/../backup/';
        // 
        if (! is_dir ( $dir )) {
            mkdir ( $dir, 0777, true ) or die ( 'Failed to create Directory' );
        }
        $size = $size ? $size : 2048;
        $sql = '';
        // 
        if ( $tablename ) {
            if(mysqli_num_rows(mysqli_query($this->conn, "SHOW TABLES LIKE '".$tablename."'")) == 1) {
             } else {
                $this->_showMsg("$tablename => ".$this->lang['table_not_found'],true);
                die();
            }
            $this->_showMsg("$tablename => ".$this->lang['backing_up_table']);
            // 
            $sql = $this->_retrieve ();
            //
            $sql .= $this->_insert_table_structure ( $tablename );
            //
            $data = mysqli_query ($this->conn, "select * from " . $tablename );
            // 
            $filename = date ( 'YmdHis' ) . "_" . $tablename;
            // 
            $num_fields = mysqli_num_fields ( $data );
            // 
            $p = 1;
            // 
            while ( $record = mysqli_fetch_array ( $data ) ) {
                // 
                $sql .= $this->_insert_record ( $tablename, $num_fields, $record );
                // 
                if (strlen ( $sql ) >= $size * 1024) {
                    $file = $filename . "_v" . $p . ".sql";
                    if ($this->_write_file ( $sql, $file, $dir )) {
                        $this->_showMsg($tablename . " - " . $p . " - " .$dir . $file ."");
                    } else {
                        $this->_showMsg(" - " . $tablename . " - ",true);
                        return false;
                    }
                    // 
                    $p ++;
                    // 
                    $sql = "";
                }
            }
            // 
            unset($data, $record);
            // 
            if ($sql != "") {
                $filename .= "_v" . $p . ".sql";
                if ($this->_write_file ( $sql, $filename, $dir )) {
                    $this->_showMsg( "- " . $tablename . " - " . $p . " - " .$dir . $filename ."");
                } else {
                    $this->_showMsg(" - " . $p . " - ");
                    return false;
                }
            }
            $this->_showMsg("");
        } else {
            $this->_showMsg('');
            // 
            if ($tables = mysqli_query ($this->conn ,  "show table status from " . $this->database )) {
                $this->_showMsg($this->lang['retrieve_tables']);
            } else {
                $this->_showMsg($this->lang['retrieve_tables_fail']);
                exit ( 0 );
            }
            // 
            $sql .= $this->_retrieve ();
            // 
            $filename = date ( 'YmdHis' ) . "_all";
            // 
            $tables = mysqli_query ($this->conn , 'SHOW TABLES' );
            // 
            $p = 1;
            // 
            while ( $table = mysqli_fetch_array ( $tables ) ) {
                //
                $tablename = $table [0];
                //
                $sql .= $this->_insert_table_structure ( $tablename );
                $data = mysqli_query($this->conn,  "select * from " . $tablename );
                $num_fields = mysqli_num_fields ( $data );

                //
                while ( $record = mysqli_fetch_array ( $data ) ) {
                    //
                    $sql .= $this->_insert_record ( $tablename, $num_fields, $record );
                    //
                    if (strlen ( $sql ) >= $size * 1000) {

                        $file = $filename . "_v" . $p . ".sql";
                        //
                        if ($this->_write_file ( $sql, $file, $dir )) {
                            $this->_showMsg("-".$this->lang['volume']."-" . $p . "-".$this->lang['backup_complete']." [ ".$dir.$file." ]");
                        } else {
                            $this->_showMsg($this->lang['volume'] . $p . " - ".$this->lang['backup_failed']."!",true);
                            return false;
                        }
                        //
                        $p ++;
                        //
                        $sql = "";
                    }
                }
            }
            // sql
            if ($sql != "") {
                $filename .= "_v" . $p . ".sql";
                if ($this->_write_file ( $sql, $filename, $dir )) {
                    $this->_showMsg("-".$this->lang['volume']."-" . $p . "-".$this->lang['backup_complete']." [ ".$dir.$filename." ]");
                } else {
                    $this->_showMsg($this->lang['volume']."-" . $p . "-".$this->lang['backup_failed'],true);
                    return false;
                }
            }
            $this->_showMsg($this->lang['congrats']."! ".$this->lang['backup_success']);
        }
    }

    //
    private function _showMsg($msg, $err=false){
        $err = $err ? $this->lang['error'].": " : '' ;
        echo $err . $msg."\n";
        flush();

    }

    /**
     *
     *
     * @return string
     */
    private function _retrieve() {
        $value = '';
        $value .= '--' . $this->ds;
        $value .= '-- MySQL database dump' . $this->ds;
        $value .= '-- Created by BackUp class, By Djunehor. ' . $this->ds;
        $value .= '-- http://github.com/djunehor ' . $this->ds;
        $value .= '--' . $this->ds;
        $value .= '-- '.$this->lang['host'].': ' . $this->host . $this->ds;
        $value .= '-- '.$this->lang['build_date'].': ' . date ( 'Y' ) . ' '.$this->lang['year'].'  ' . date ( 'm' ) . ' '.$this->lang['month'].' ' . date ( 'd' ) . ' '.$this->lang['day'].' ' . date ( 'H:i' ) . $this->ds;
        $value .= '-- MySQL: ' . mysqli_get_server_info ($this->conn) . $this->ds;
        $value .= '-- PHP: ' . phpversion () . $this->ds;
        $value .= $this->ds;
        $value .= '--' . $this->ds;
        $value .= '-- '.$this->lang['database'].': `' . $this->database . '`' . $this->ds;
        $value .= '--' . $this->ds . $this->ds;
        $value .= '-- -------------------------------------------------------';
        $value .= $this->ds . $this->ds;
        return $value;
    }

    /**
     *
     *
     * @param $table
     * @return string
     */
    private function _insert_table_structure($table) {
        $sql = '';
        $sql .= "--" . $this->ds;
        $sql .= "-- " . $table . $this->ds;
        $sql .= "--" . $this->ds . $this->ds;

        //
        $sql .= "DROP TABLE IF EXISTS `" . $table . '`' . $this->sqlEnd . $this->ds;
        //
        $res = mysqli_query($this->conn,  'SHOW CREATE TABLE `' . $table . '`' );
        $row = mysqli_fetch_array ( $res );
        $sql .= $row [1];
        $sql .= $this->sqlEnd . $this->ds;
        //
        $sql .= $this->ds;
        $sql .= "--" . $this->ds;
        $sql .= "--  " . $table . $this->ds;
        $sql .= "--" . $this->ds;
        $sql .= $this->ds;
        return $sql;
    }

    /**
     *
     *
     * @param string $table
     * @param int $num_fields
     * @param array $record
     * @return string
     */
    private function _insert_record($table, $num_fields, $record) {
        //
        $insert = '';
        $comma = "";
        $insert .= "INSERT INTO `" . $table . "` VALUES(";
        //
        for($i = 0; $i < $num_fields; $i ++) {
            $insert .= ($comma . "'" . mysqli_real_escape_string ($this->conn,  $record [$i] ) . "'");
            $comma = ",";
        }
        $insert .= ");" . $this->ds;
        return $insert;
    }

    /**
     *
     *
     * @param string $sql
     * @param string $filename
     * @param string $dir
     * @return boolean
     */
    private function _write_file($sql, $filename, $dir) {
        $dir = $dir ? $dir : './backup/';
        //
        if (! is_dir ( $dir )) {
            mkdir ( $dir, 0777, true );
        }
        $re = true;
        if (! @$fp = fopen ( $dir . $filename, "w+" )) {
            $re = false;
            $this->_showMsg($this->lang['open_sql_fail'],true);
        }
        if (! @fwrite ( $fp, $sql )) {
            $re = false;
            $this->_showMsg($this->lang['write_sql_fail'],true);
        }
        if (! @fclose ( $fp )) {
            $re = false;
            $this->_showMsg($this->lang['close_sql_fail'],true);
        }
        return $re;
    }

    /*
     *
     * ---------------------------------------------------------------
     */

    /**
     *
     *
     * @param string $sqlfile
     */
    function restore($sqlfile) {
        //
        if (! file_exists ( $sqlfile )) {
            $this->_showMsg($this->lang['sql_file_not_exist'],true);
            exit ();
        }
        $this->lock ( $this->database );
        //
        $sqlpath = pathinfo ( $sqlfile );
        $this->sqldir = $sqlpath ['dirname'];
        //
        $volume = explode ( "_v", $sqlfile );
        $volume_path = $volume [0];
        $this->_showMsg($this->lang['do_not_refresh']);
        $this->_showMsg($this->lang['please_wait']);
        if (empty ( $volume [1] )) {
            $this->_showMsg ( $this->lang['importing_sql']."：" . $sqlfile);
            //
            if ($this->_import ( $sqlfile )) {
                $this->_showMsg( $this->lang['db_import_succeed']);
            } else {
                 $this->_showMsg($this->lang['db_import_failed'],true);
                exit ();
            }
        } else {
            //
            $volume_id = explode ( ".sq", $volume [1] );
            // $volume_id
            $volume_id = intval ( $volume_id [0] );
            while ( $volume_id ) {
                $tmpfile = $volume_path . "_v" . $volume_id . ".sql";
                //
                if (file_exists ( $tmpfile )) {
                    //
                    $this->_showMsg($this->lang['importing_volume']." $volume_id ：" . $tmpfile);
                    if ($this->_import ( $tmpfile )) {

                    } else {
                        $volume_id = $volume_id ? $volume_id :1;
                        exit ( $this->lang['importing_volume']."：" . $tmpfile . ' '.$this->lang['importing_volume_failed'] );
                    }
                } else {
                    $this->_showMsg($this->lang['importing_volume_succeed']);
                    return;
                }
                $volume_id ++;
            }
        }if (empty ( $volume [1] )) {
            $this->_showMsg ( $this->lang['importing_sql']."：" . $sqlfile);
            //
            if ($this->_import ( $sqlfile )) {
                $this->_showMsg( $this->lang['db_import_succeed']);
            } else {
                 $this->_showMsg($this->lang['db_import_failed'],true);
                exit ();
            }
        } else {
            //
            $volume_id = explode ( ".sq", $volume [1] );
            // $volume_id
            $volume_id = intval ( $volume_id [0] );
            while ( $volume_id ) {
                $tmpfile = $volume_path . "_v" . $volume_id . ".sql";
                //
                if (file_exists ( $tmpfile )) {
                    //
                    $this->_showMsg($this->lang['importing_volume']."$volume_id ：" . $tmpfile);
                    if ($this->_import ( $tmpfile )) {

                    } else {
                        $volume_id = $volume_id ? $volume_id :1;
                        exit ( $this->lang['import_volume']."：" . $tmpfile . ' '.$this->lang['importing_volume_failed'] );
                    }
                } else {
                    $this->_showMsg($this->lang['importing_volume_succeed']);
                    return;
                }
                $volume_id ++;
            }
        }
    }

    /**
     *
     *
     * @param string $sqlfile
     * @return boolean
     */
    private function _import($sqlfile) {
        //
        $sqls = array ();
        $f = fopen ( $sqlfile, "rb" );
        //
        $create_table = '';
        while ( ! feof ( $f ) ) {
            //
            $line = fgets ( $f );
            //
            //
            if (! preg_match ( '/;/', $line ) || preg_match ( '/ENGINE=/', $line )) {
                //
                $create_table .= $line;
                //
                if (preg_match ( '/ENGINE=/', $create_table)) {
                    //
                    $this->_insert_into($create_table);
                    //
                    $create_table = '';
                }
                //
                continue;
            }
            //
            $this->_insert_into($line);
        }
        fclose ( $f );
        return true;
    }

    //
    private function _insert_into($sql){
        if (! mysqli_query($this->conn,  trim ( $sql ) )) {
            $this->_showMsg(mysqli_error ($this->conn), true);
            return false;
        }
    }

    /*
     * -------------------------------end---------------------------------
     */

    //
    private function close() {
        mysqli_close ( $this->db );
    }

    //
    private function lock($tablename, $op = "WRITE") {
        if (mysqli_query($this->conn,  "lock tables " . $tablename . " " . $op ))
            return true;
        else
            return false;
    }

    //
    private function unlock() {
        if (mysqli_query($this->conn,  "unlock tables" ))
            return true;
        else
            return false;
    }

    //
    function __destruct() {
        if($this->db){
            mysqli_query($this->conn,  "unlock tables", $this->db );
            mysqli_close ( $this->db );
        }
    }

}
