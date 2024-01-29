<?php
class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'];
        $this->db_name = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
    }

    public function connect()
    {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

        if ($this->conn->connect_error) {
            die('Error de conexión: ' . $this->conn->connect_error);
        }

        return $this->conn;
    }

    public function dbQuery($query)
    {
        return $this->conn->query($query);
    }

    public function dbFetchAssoc($result)
    {
        return $result->fetch_assoc();
    }

    public function dbFetchAll($result)
    {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function dbQuery_insert($query)
    {
        $this->conn->query($query);
        return $this->conn->insert_id;
    }
}
