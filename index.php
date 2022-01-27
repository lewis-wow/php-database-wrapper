<?php

/**
 * DatabaseConnection
 */
class DatabaseConnection extends PDO {
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
        $query = "SELECT $what FROM `$this->table` WHERE $where";
        $prep = $this->db->prepare($query);

        if (!$this->on_query($prep)) return self::TERMINATED;

        $res = $prep->execute($params);
        if (!$res) return self::FAIL;
        if ($prep->rowCount() == 0) return self::EMPTY;

        return $prep->fetch(PDO::FETCH_ASSOC);
    }

    public function selectAll($what, $where, ...$params) {
        $query = "SELECT $what FROM `$this->table` WHERE $where";
        $prep = $this->db->prepare($query);

        if (!$this->on_query($prep)) return self::TERMINATED;

        $res = $prep->execute($params);
        if (!$res) return self::FAIL;
        if ($prep->rowCount() == 0) return self::EMPTY;

        return $prep->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectAllIteratee($what, $where, $params, $iteratee) {
        $query = "SELECT $what FROM `$this->table` WHERE $where";
        $prep = $this->db->prepare($query);

        if (!$this->on_query($prep)) return self::TERMINATED;

        $res = $prep->execute($params);
        if (!$res) return self::FAIL;

        while ($row = $prep->fetch()) {
            $iteratee($row, $prep);
        }

        return $prep;
    }

    public function delete($where, ...$params) {
        $query = "DELETE FROM `$this->table` WHERE $where";
        $prep = $this->db->prepare($query);

        if (!$this->on_query($prep)) return self::TERMINATED;

        $res = $prep->execute($params);
        if (!$res) return self::FAIL;

        return $prep;
    }

    public function update($data, $where, ...$params) {
        $dataKeys = array_keys($data);
        $keys = "";
        foreach ($dataKeys as $key) {
            $keys .= "$key=:$key,";
        }
        $keys = substr($keys, 0, -1);
        $query = "UPDATE `$this->table` SET $keys WHERE $where";

        $prep = $this->db->prepare($query);

        if (!$this->on_query($prep)) return self::TERMINATED;

        $res = $prep->execute(array_merge($data, ...$params));
        if (!$res) return self::FAIL;

        return $prep;
    }

    public function insert($data) {
        $dataKeys = array_keys($data);
        $keys = implode(",", $dataKeys);
        $valuesHooks = ":" . implode(", :", $dataKeys);
        $query = "INSERT INTO `$this->table` ($keys) VALUES ($valuesHooks)";

        $prep = $this->db->prepare($query);

        if (!$this->on_query($prep)) return self::TERMINATED;

        $res = $prep->execute($data);
        if (!$res) return self::FAIL;

        return $prep;
    }

    public function overwrite($data, $where, ...$params) {
        $res = $this->select("*", $where, ...$data);

        if ($res && $res->num_rows > 0) return $this->update($data, $where, ...$params);

        return $this->insert($data);
    }

    public function query($q, ...$params) {
        $prep = $this->db->prepare($q);

        if (!$this->on_query($prep)) return self::TERMINATED;

        if (!$prep->execute($params)) return false;

        return $prep;
    }

    public function lastInsertId() {
        return $this->db->lastInsertId();
    }

    public function on_query($q) {
        return true;
    }

    public function close() {
        $this->db = null;
        $this->table = null;

        return null;
    }
}

/**
 * usage
 */

/** users.php file */
return DatabaseTable::connect($db, "users", new class extends DatabaseTableUtils {
    public function login($email, $password) {
        return $this->select("*", "email=? AND password=?", $email, $password);
    }
});
