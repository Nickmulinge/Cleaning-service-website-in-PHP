<?php
require_once __DIR__ . '/../config/database.php';

class Service {
    private $conn;
    private $table_name = "services";

    public $id;
    public $name;
    public $description;
    public $base_price;
    public $duration_minutes;
    public $category;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE status = 'active' ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->base_price = $row['base_price'];
            $this->duration_minutes = $row['duration_minutes'];
            $this->category = $row['category'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, description=:description, base_price=:base_price, 
                      duration_minutes=:duration_minutes, category=:category";

        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->base_price = htmlspecialchars(strip_tags($this->base_price));
        $this->duration_minutes = htmlspecialchars(strip_tags($this->duration_minutes));
        $this->category = htmlspecialchars(strip_tags($this->category));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":base_price", $this->base_price);
        $stmt->bindParam(":duration_minutes", $this->duration_minutes);
        $stmt->bindParam(":category", $this->category);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
