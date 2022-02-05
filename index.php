<?php

/**
 * DatabaseConnection
 */
class DatabaseConnection extends PDO {

    /**
     * @param string $table
     * @param DatabaseTableUtils $tableUtil
     * 
     * @return DatabaseTableUtils
     */
    public function connectTable($table, $tableUtil) {
        $tableUtil->table = $table;
        $tableUtil->db = $this;

        return $tableUtil;
    }

    /**
     * @param string $table
     * @param string $what
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function basicSelect($table, $what, $where = "", ...$params) {
        $query = "SELECT $what FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $table
     * @param string $what
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function select($table, $what, $where = "", ...$params) {
        $res = $this->basicSelect($table, $what, $where, ...$params);
        return !$res ? [] : $res;
    }

    /**
     * @param string $table
     * @param string $what
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function selectAll($table, $what, $where = "", ...$params) {
        $query = "SELECT $what FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $table
     * @param string $what
     * @param string $where
     * @param array $params
     * @param callable $iteratee
     * 
     * @return [type]
     */
    public function selectAllIteratee($table, $what, $where, $params, $iteratee) {
        $query = "SELECT $what FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        while ($row = $prep->fetch()) {
            $iteratee($row, $prep);
        }

        return $prep;
    }

    /**
     * @param string $table
     * @param string $where
     * @param mixed ...$params
     * 
     * @return [type]
     */
    public function delete($table, $where = "", ...$params) {
        $query = "DELETE FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep;
    }

    /**
     * @param string $table
     * @param array $data
     * @param string $where
     * @param mixed ...$params
     * 
     * @return [type]
     */
    public function update($table, $data, $where = "", ...$params) {
        $dataKeys = array_keys($data);
        $keys = "";
        foreach ($dataKeys as $key) {
            $keys .= "$key=:$key,";
        }
        $keys = substr($keys, 0, -1);
        $query = "UPDATE `$table` SET $keys WHERE $where";

        $prep = $this->prepare($query);
        $prep->execute(array_merge($data, ...$params));

        return $prep;
    }

    /**
     * @param string $table
     * @param array $data
     * 
     * @return [type]
     */
    public function insert($table, $data) {
        $dataKeys = array_keys($data);
        $keys = implode(",", $dataKeys);
        $valuesHooks = ":" . implode(", :", $dataKeys);
        $query = "INSERT INTO `$table` ($keys) VALUES ($valuesHooks)";

        $prep = $this->prepare($query);
        $prep->execute($data);

        return $prep;
    }

    /**
     * @param string $table
     * @param array $data
     * @param string $where
     * @param mixed ...$params
     * 
     * @return [type]
     */
    public function overwrite($table, $data, $where = "", ...$params) {
        $res = $this->select($table, "*", $where, ...$data);

        if (!empty($res)) return $this->update($table, $data, $where, ...$params);

        return $this->insert($table, $data);
    }

    /**
     * @param string $q
     * @param mixed ...$params
     * 
     * @return [type]
     */
    public function safeQuery($q, ...$params) {
        $prep = $this->prepare($q);
        $prep->execute($params);

        return $prep;
    }

    /**
     * @param string $query
     * @param mixed ...$params
     * 
     * @return array
     */
    public function run($query, ...$params) {
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }
}

class DatabaseTableUtils {
    public $table;
    public $db;

    const FAIL = 0;
    const EMPTY = -1;

    /**
     * @param string $what
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function select($what, $where, ...$params) {
        return $this->db->select($this->table, $what, $where, ...$params);
    }

    /**
     * @param string $what
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function selectAll($what, $where, ...$params) {
        return $this->db->selectAll($this->table, $what, $where, ...$params);
    }

    /**
     * @param string $what
     * @param string $where
     * @param array $params
     * @param callable $iteratee
     * 
     * @return array
     */
    public function selectAllIteratee($what, $where, $params, $iteratee) {
        return $this->db->selectAllIteratee($this->table, $what, $where, $params, $iteratee);
    }

    /**
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function delete($where, ...$params) {
        return $this->db->delete($this->table, $where, ...$params);
    }

    /**
     * @param array $data
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function update($data, $where, ...$params) {
        return $this->db->update($this->table, $data, $where, ...$params);
    }

    /**
     * @param array $data
     * 
     * @return array
     */
    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    /**
     * @param array $data
     * @param string $where
     * @param mixed ...$params
     * 
     * @return array
     */
    public function overwrite($data, $where, ...$params) {
        return $this->db->overwrite($this->table, $data, $where, ...$params);
    }

    /**
     * @param string $q
     * @param mixed ...$params
     * 
     * @return object
     */
    public function query($q, ...$params) {
        return $this->db->run($q, ...$params);
    }

    /**
     * @return mixed
     */
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }

    /**
     * @return null
     */
    public function close() {
        $this->db = null;
        $this->table = null;

        return null;
    }

    /**
     * @param string $table
     * 
     * @return string
     */
    public function migrate($table) {
        $this->table = $table;
        return $table;
    }

    /**
     * @param int $length
     * 
     * @return string
     */
    public function token($length = 64) { // 64 = 32
        $length = ($length < 4) ? 4 : $length;
        return bin2hex(random_bytes(($length - ($length % 2)) / 2));
    }

    /**
     * @param mixed $var
     */
    public function debug($var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }

    /**
     * @param mixed $var
     */
    public function var_dump_pre($var) {
        return $this->debug($var);
    }
}
