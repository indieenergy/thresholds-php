<?php

class SQLQuery {
    protected $_dbHandle;
    protected $_result;
    protected $_query;
    protected $_table;

    protected $_describe = array();

    protected $_orderBy;
    protected $_order;
    protected $_extraConditions;
    protected $_limit;

    /** Connects to database **/
    
    function connect($address, $account, $pwd, $name) {
        $this->_dbHandle = @mysql_connect($address, $account, $pwd);
        if ($this->_dbHandle != 0) {
            if (mysql_select_db($name, $this->_dbHandle)) {
                return 1;
            }
            else {
                return 0;
            }
        }
        else {
            return 0;
        }
    }

    /** Disconnects from database **/

    function disconnect() {
        if (@mysql_close($this->_dbHandle) != 0) {
            return 1;
        }  else {
            return 0;
        }
    }

    /** Select Query **/

    function where($field, $value) {
        $this->_extraConditions .= '`'.$this->_model.'`.`'.$field.'` = \''.mysql_real_escape_string($value).'\' AND ';
    }

    function like($field, $value) {
        $this->_extraConditions .= '`'.$this->_model.'`.`'.$field.'` LIKE \'%'.mysql_real_escape_string($value).'%\' AND ';
    }

    function orderBy($orderBy, $order = 'ASC') {
        $this->_orderBy = $orderBy;
        $this->_order = $order;
    }
    
    function search() {

        $from = '`'.$this->_table.'` as `'.$this->_model.'` ';
        $conditions = '\'1\'=\'1\' AND ';
        
        if ($this->id) {
            $conditions .= '`'.$this->_model.'`.`id` = \''.mysql_real_escape_string($this->id).'\' AND ';
        }
        
        if ($this->_extraConditions) {
            $conditions .= $this->_extraConditions;
        }
        
        $conditions = substr($conditions,0,-4);
        
        if (isset($this->_orderBy)) {
            $conditions .= ' ORDER BY `'.$this->_model.'`.`'.$this->_orderBy.'` '.$this->_order;
        }
        
        if (isset($this->_page)) {
            $offset = ($this->_page-1)*$this->_limit;
            $conditions .= ' LIMIT '.$this->_limit.' OFFSET '.$offset;
        }
        
        $this->_query = 'SELECT * FROM '.$from.' WHERE '.$conditions;
        
        $this->_result = mysql_query($this->_query, $this->_dbHandle);
        
        $result = array();
        $table = array();
        $field = array();
        $tempResults = array();
        $numOfFields = mysql_num_fields($this->_result);
        
        for ($i = 0; $i < $numOfFields; ++$i) {
            array_push($table,mysql_field_table($this->_result, $i));
            array_push($field,mysql_field_name($this->_result, $i));
        }
        
        if (mysql_num_rows($this->_result) > 0 ) {
            while ($row = mysql_fetch_row($this->_result)) {
                for ($i = 0;$i < $numOfFields; ++$i) {
                    $tempResults[$table[$i]][$field[$i]] = $row[$i];
                }
                array_push($result,$tempResults);
            }
            
            if (mysql_num_rows($this->_result) == 1 && $this->id != null) {
                mysql_free_result($this->_result);
                $this->clear();
                return($result[0]);
            }
            else {
                mysql_free_result($this->_result);
                $this->clear();
                return($result);
            }
        }
        else {
            mysql_free_result($this->_result);
            $this->clear();
            return $result;
        }
    }
    
    /** Custom SQL Query **/

    function custom($query) {

        $this->_result = mysql_query($query, $this->_dbHandle);

        $result = array();
        $table = array();
        $field = array();
        $tempResults = array();

        if(substr_count(strtoupper($query),"SELECT")>0) {
            if (mysql_num_rows($this->_result) > 0) {
                $numOfFields = mysql_num_fields($this->_result);
                for ($i = 0; $i < $numOfFields; ++$i) {
                    array_push($table,mysql_field_table($this->_result, $i));
                    array_push($field,mysql_field_name($this->_result, $i));
                }
                while ($row = mysql_fetch_row($this->_result)) {
                    for ($i = 0;$i < $numOfFields; ++$i) {
                        $table[$i] = ucfirst($table[$i]);
                        $tempResults[$table[$i]][$field[$i]] = $row[$i];
                    }
                    array_push($result,$tempResults);
                }
            }
            mysql_free_result($this->_result);
        }
        $this->clear();
        return($result);
    }

    /** Describes a Table **/

    protected function _describe() {

        $this->_describe = null;

        if (!$this->_describe) {
            $this->_describe = array();
            $query = 'DESCRIBE '.$this->_table;
            $this->_result = mysql_query($query, $this->_dbHandle);
            while ($row = mysql_fetch_row($this->_result)) {
                array_push($this->_describe,$row[0]);
            }

            mysql_free_result($this->_result);
        }

        foreach ($this->_describe as $field) {
            $this->$field = null;
        }
    }

    /** Delete an Object **/

    function delete() {
        if ($this->id) {
            $query = 'DELETE FROM '.$this->_table.' WHERE `id`=\''.mysql_real_escape_string($this->id).'\'';		
            $this->_result = mysql_query($query, $this->_dbHandle);
            $this->clear();
            if ($this->_result == 0) {
                /** Error Generation **/
                return -1;
            }
        }
        else {
            /** Error Generation **/
            return -1;
        }
    }

    /** Saves an Object i.e. Updates/Inserts Query **/

    function save() {
        $query = '';
        if (isset($this->id)) {
            $updates = '';
            foreach ($this->_describe as $field) {
                if ($this->$field) {
                    $updates .= '`'.$field.'` = \''.mysql_real_escape_string($this->$field).'\',';
                }
            }

            $updates = substr($updates,0,-1);

            $query = 'UPDATE '.$this->_table.' SET '.$updates.' WHERE `id`=\''.mysql_real_escape_string($this->id).'\'';			
        }
        else {
            $fields = '';
            $values = '';
            foreach ($this->_describe as $field) {
                if ($this->$field) {
                    $fields .= '`'.$field.'`,';
                    $values .= '\''.mysql_real_escape_string($this->$field).'\',';
                }
            }
            $values = substr($values,0,-1);
            $fields = substr($fields,0,-1);

            $query = 'INSERT INTO '.$this->_table.' ('.$fields.') VALUES ('.$values.')';
        }
        $this->_result = mysql_query($query, $this->_dbHandle);
        $this->clear();
        if ($this->_result == 0) {
            /** Error Generation **/
            return -1;
        }
    }

    /** Clear All Variables **/

    function clear() {
        foreach($this->_describe as $field) {
            $this->$field = null;
        }

        $this->_orderby = null;
        $this->_extraConditions = null;
        $this->_page = null;
        $this->_order = null;
    }

    /** Get error string **/

    function getError() {
        return mysql_error($this->_dbHandle);
    }
}