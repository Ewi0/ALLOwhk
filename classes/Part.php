<?php
require_once 'Database.php';

class Part {
    private $db;
    private $data = [];
    public $id, $name, $quantity, $description, $article, $price, $shelf;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function load($id) {
        $stmt = $this->db->prepare("SELECT * FROM parts WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($part = $result->fetch_assoc()) {
            $this->id = $part['id'];
            $this->name = $part['part_name'];
            $this->quantity = $part['quantity'];
            $this->description = $part['description'];
            $this->article = $part['article'];
            $this->price = $part['price'];
            $this->shelf = $part['shelf'];
            return true;
        }
        return false;
    }

    public function update() {
        $stmt = $this->db->prepare("
            UPDATE parts SET part_name = ?, quantity = ?, description = ?, article = ?, price = ?, shelf = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sissdsi",
            $this->name,
            $this->quantity,
            $this->description,
            $this->article,
            $this->price,
            $this->shelf,
            $this->id
        );
        return $stmt->execute();
    }

    public function exists($article, $barcode) {
        $stmt = $this->db->prepare("SELECT * FROM parts WHERE article = ? OR barcode = ?");
        $stmt->bind_param("ss", $article, $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // false если не найдено
    }

    public function generateEAN13() {
        $ean = '475' . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        $digits = str_split($ean);
        $sum = 0;
        foreach ($digits as $i => $d) {
            $sum += $d * ($i % 2 === 0 ? 1 : 3);
        }
        $checksum = (10 - ($sum % 10)) % 10;
        return $ean . $checksum;
    }

    public function insert($data) {
        $stmt = $this->db->prepare("
            INSERT INTO parts (article, part_name, quantity, description, price, shelf, barcode)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisdss",
            $data['article'],
            $data['part_name'],
            $data['quantity'],
            $data['description'],
            $data['price'],
            $data['shelf'],
            $data['barcode']
        );
        return $stmt->execute();
    }
    
    public function searchParts($search = '', $offset = 0, $limit = 10) {
        $query = "SELECT * FROM parts";
        $params = [];
        $types = '';
    
        if (!empty($search)) {
            $query .= " WHERE part_name LIKE ? OR description LIKE ? OR article LIKE ? OR barcode LIKE ?";
            $search_term = "%$search%";
            $params = [$search_term, $search_term, $search_term, $search_term];
            $types = 'ssss';
        }
    
        $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    
        $stmt = $this->db->prepare($query);
    
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function countParts($search = '') {
        $query = "SELECT COUNT(*) AS total FROM parts";
        $params = [];
        $types = '';
    
        if (!empty($search)) {
            $query .= " WHERE part_name LIKE ? OR description LIKE ? OR article LIKE ? OR barcode LIKE ?";
            $search_term = "%$search%";
            $params = [$search_term, $search_term, $search_term, $search_term];
            $types = 'ssss';
        }
    
        $stmt = $this->db->prepare($query);
    
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        return $row['total'] ?? 0;
    }    
}