# php-database-wrapper

## auth.php
```
// PDO database connection object, table name, anonymous class that extends DatabaseTableUtils
return DatabaseTable::connect($db, "users", new class extends DatabaseTableUtils {
    public function login($email, $password) {
        return $this->select("email, name, surname, id", "email=? AND password=?", $email, $password);
    }
    
    public function register($email, $password) {
        return $this->insert([
          "email" => $email,
          "password" => $password
        ]);
    }
});
```

## somewhere.php
```
$auth = require_once "./auth.php";

/*
  .
  .
  .
*/

$user = $auth->login($email, $password);
if(!$user || empty($user)) echo "Bad login";
echo "Logged in as: $user[name]";
```
