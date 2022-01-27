<?php

/**
 * DatabaseConnection
 */
class DatabaseConnection extends PDO {
    public function basicSelect($table, $what, $where = "", ...$params) {
        $query = "SELECT $what FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep->fetch(PDO::FETCH_ASSOC);
    }

    public function select($table, $what, $where = "", ...$params) {
        $res = $this->basicSelect($table, $what, $where, ...$params);
        return !$res ? [] : $res;
    }

    public function selectAll($table, $what, $where = "", ...$params) {
        $query = "SELECT $what FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectAllIteratee($table, $what, $where, $params, $iteratee) {
        $query = "SELECT $what FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        while ($row = $prep->fetch()) {
            $iteratee($row, $prep);
        }

        return $prep;
    }

    public function delete($table, $where = "", ...$params) {
        $query = "DELETE FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep;
    }

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

    public function insert($table, $data) {
        $dataKeys = array_keys($data);
        $keys = implode(",", $dataKeys);
        $valuesHooks = ":" . implode(", :", $dataKeys);
        $query = "INSERT INTO `$table` ($keys) VALUES ($valuesHooks)";

        $prep = $this->prepare($query);
        $prep->execute($data);

        return $prep;
    }

    public function overwrite($table, $data, $where = "", ...$params) {
        $res = $this->select($table, "*", $where, ...$data);

        if ($res && $res->num_rows > 0) return $this->update($table, $data, $where, ...$params);

        return $this->insert($table, $data);
    }

    public function safeQuery($q, ...$params) {
        $prep = $this->prepare($q);
        $prep->execute($params);

        return $prep;
    }

    public function run($table, $what, $where = "", ...$params) {
        $query = "SELECT $what FROM `$table` WHERE $where";
        $prep = $this->prepare($query);
        $prep->execute($params);

        return $prep;
    }
}

class DatabaseTable {
    public const FAIL = 0;
    public const EMPTY = -1;
    public const NOCHANGE = -2;
    public const TERMINATED = -3;

    static public function connect($db, $table, $tableUtil) {
        $tableUtil->db = $db;
        $tableUtil->table = $table;

        return $tableUtil;
    }
}

class DatabaseTableUtils extends DatabaseTable {
    protected $db;
    protected $table;

    public function select($what, $where, ...$params) {
        return $this->db->select($this->table, $what, $where, ...$params);
    }

    public function selectAll($what, $where, ...$params) {
        return $this->db->selectAll($this->table, $what, $where, ...$params);
    }

    public function selectAllIteratee($what, $where, $params, $iteratee) {
        return $this->db->selectAllIteratee($this->table, $what, $where, $params, $iteratee);
    }

    public function delete($where, ...$params) {
        return $this->db->delete($this->table, $where, ...$params);
    }

    public function update($data, $where, ...$params) {
        return $this->db->update($this->table, $data, $where, ...$params);
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function overwrite($data, $where, ...$params) {
        return $this->db->overwrite($this->table, $data, $where, ...$params);
    }

    public function query($what, $where, ...$params) {
        return $this->db->run($this->table, $what, $where, ...$params);
    }

    public function lastInsertId() {
        return $this->db->lastInsertId();
    }

    public function close() {
        $this->db = null;
        $this->table = null;

        return null;
    }
}
