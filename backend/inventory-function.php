<?php
// inventory_functions.php
require_once '../config/db.php'; // This file contains your $pdo connection

error_reporting(E_ALL);
ini_set('display_errors', 1);



class InventoryManager {
    private $conn;
    
    public function __construct() {
        // Use the global $pdo connection from db.php
        global $pdo;
        $this->conn = $pdo;
    }
    
    // Get dashboard statistics
    // Get dashboard statistics with previous period comparison
public function getDashboardStats() {
    // Current period (now)
    $current = $this->conn->query("
        SELECT 
            (SELECT COUNT(*) FROM inventory_items) as total_items,
            (SELECT COUNT(*) FROM categories) as total_categories,
            (SELECT COUNT(*) FROM inventory_items WHERE quantity <= reorder_level AND quantity > 0) as low_stock_items,
            (SELECT COUNT(*) FROM inventory_items WHERE quantity <= 0) as out_of_stock_items,
            (SELECT SUM(quantity * price) FROM inventory_items) as total_inventory_value,
            (SELECT COUNT(DISTINCT supplier_id) FROM inventory_items) as active_suppliers
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Previous period (e.g., last month)
    $firstOfMonth = date('Y-m-01');
    $previous = $this->conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM inventory_items WHERE created_at < :firstOfMonth) as total_items,
            (SELECT COUNT(*) FROM categories WHERE created_at < :firstOfMonth) as total_categories,
            (SELECT SUM(quantity * price) FROM inventory_items WHERE created_at < :firstOfMonth) as total_inventory_value
    ");
    $previous->execute([':firstOfMonth' => $firstOfMonth]);
    $previousData = $previous->fetch(PDO::FETCH_ASSOC);
    
    // Start with current stats
    $stats = $current;
    
    // Items growth
    if ($previousData['total_items'] > 0) {
        $stats['items_growth'] = round((($current['total_items'] - $previousData['total_items']) / $previousData['total_items']) * 100, 1);
        $stats['items_growth_abs'] = $current['total_items'] - $previousData['total_items'];
    } else {
        $stats['items_growth'] = 100;
        $stats['items_growth_abs'] = $current['total_items'];
    }
    
    // Categories growth
    if ($previousData['total_categories'] > 0) {
        $stats['categories_growth'] = round((($current['total_categories'] - $previousData['total_categories']) / $previousData['total_categories']) * 100, 1);
        $stats['categories_growth_abs'] = $current['total_categories'] - $previousData['total_categories'];
    } else {
        $stats['categories_growth'] = 100;
        $stats['categories_growth_abs'] = $current['total_categories'];
    }
    
    // Value growth
    if ($previousData['total_inventory_value'] > 0 && $previousData['total_inventory_value'] != 0) {
        $stats['value_growth'] = round((($current['total_inventory_value'] - $previousData['total_inventory_value']) / $previousData['total_inventory_value']) * 100, 1);
    } else {
        $stats['value_growth'] = 0;
    }
    
    return $stats;
}


   // Get recently added items
public function getRecentAdditions($days = 7) {
    $query = "SELECT COUNT(*) as count FROM inventory_items 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Get low stock count
public function getLowStockCount() {
    $query = "SELECT COUNT(*) as count FROM inventory_items 
              WHERE quantity <= reorder_level AND quantity > 0";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
    // Get all inventory items with category and supplier info
    public function getInventoryItems($page = 1, $limit = 10, $filter = 'all', $category = null) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT 
                    i.*,
                    c.category_name,
                    c.color_class as category_color,
                    s.supplier_name,
                    CASE 
                        WHEN i.quantity <= 0 THEN 'out_of_stock'
                        WHEN i.quantity <= i.reorder_level THEN 'low_stock'
                        ELSE 'in_stock'
                    END as status
                  FROM inventory_items i
                  LEFT JOIN categories c ON i.category_id = c.id
                  LEFT JOIN suppliers s ON i.supplier_id = s.id";
        
        // Apply filters
        $whereConditions = [];
        if ($filter !== 'all') {
            if ($filter === 'in_stock') {
                $whereConditions[] = "i.quantity > i.reorder_level";
            } elseif ($filter === 'low_stock') {
                $whereConditions[] = "i.quantity <= i.reorder_level AND i.quantity > 0";
            } elseif ($filter === 'out_of_stock') {
                $whereConditions[] = "i.quantity <= 0";
            }
        }
        
        if ($category && $category !== 'All Categories') {
            $whereConditions[] = "c.category_name = :category";
        }
        
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $query .= " ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if ($category && $category !== 'All Categories') {
            $stmt->bindParam(':category', $category);
        }
        
        // Fix for bindParam with LIMIT and OFFSET
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total count for pagination
    public function getTotalCount($filter = 'all', $category = null) {
        $query = "SELECT COUNT(*) as total FROM inventory_items i
                  LEFT JOIN categories c ON i.category_id = c.id";
        
        $whereConditions = [];
        if ($filter !== 'all') {
            if ($filter === 'in_stock') {
                $whereConditions[] = "i.quantity > i.reorder_level";
            } elseif ($filter === 'low_stock') {
                $whereConditions[] = "i.quantity <= i.reorder_level AND i.quantity > 0";
            } elseif ($filter === 'out_of_stock') {
                $whereConditions[] = "i.quantity <= 0";
            }
        }
        
        if ($category && $category !== 'All Categories') {
            $whereConditions[] = "c.category_name = :category";
        }
        
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($category && $category !== 'All Categories') {
            $stmt->bindParam(':category', $category);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // Get single item by ID
    public function getItemById($id) {
        $query = "SELECT 
                    i.*,
                    c.category_name,
                    c.color_class,
                    s.supplier_name,
                    s.contact_person,
                    s.email as supplier_email,
                    s.phone as supplier_phone,
                    CASE 
                        WHEN i.quantity <= 0 THEN 'out_of_stock'
                        WHEN i.quantity <= i.reorder_level THEN 'low_stock'
                        ELSE 'in_stock'
                    END as status
                  FROM inventory_items i
                  LEFT JOIN categories c ON i.category_id = c.id
                  LEFT JOIN suppliers s ON i.supplier_id = s.id
                  WHERE i.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Add new inventory item
    public function addItem($data) {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            if (empty($data['item_name']) || empty($data['sku']) || empty($data['category']) || empty($data['supplier'])) {
                throw new Exception("Required fields are missing");
            }
            
            // Check if SKU already exists
            if ($this->skuExists($data['sku'])) {
                throw new Exception("SKU already exists");
            }
            
            // Get category ID
            $category_id = $this->getOrCreateCategory($data['category']);
            
            // Get supplier ID
            $supplier_id = $this->getOrCreateSupplier($data['supplier']);
            
            // Insert inventory item
            $query = "INSERT INTO inventory_items 
                      (item_name, sku, category_id, quantity, price, reorder_level, description, supplier_id) 
                      VALUES 
                      (:item_name, :sku, :category_id, :quantity, :price, :reorder_level, :description, :supplier_id)";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':item_name', $data['item_name']);
            $stmt->bindParam(':sku', $data['sku']);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':quantity', $data['quantity']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':reorder_level', $data['reorder_level']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':supplier_id', $supplier_id);
            
            $stmt->execute();
            $item_id = $this->conn->lastInsertId();
            
            // Record stock movement
            $this->recordStockMovement($item_id, 'in', $data['quantity'], 0, $data['quantity'], 'Initial stock');
            
            // Update category item count
            $this->updateCategoryItemCount($category_id);
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Item added successfully', 'id' => $item_id];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Error adding item: ' . $e->getMessage()];
        }
    }
    
    // Update inventory item
    public function updateItem($id, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Get current item data for stock movement tracking
            $current_item = $this->getItemById($id);
            
            if (!$current_item) {
                throw new Exception("Item not found");
            }
            
            // Check if SKU exists for another item
            if ($this->skuExists($data['sku'], $id)) {
                throw new Exception("SKU already exists for another item");
            }
            
            // Get category ID
            $category_id = $this->getOrCreateCategory($data['category']);
            
            // Get supplier ID
            $supplier_id = $this->getOrCreateSupplier($data['supplier']);
            
            // Update inventory item
            $query = "UPDATE inventory_items 
                      SET item_name = :item_name,
                          sku = :sku,
                          category_id = :category_id,
                          quantity = :quantity,
                          price = :price,
                          reorder_level = :reorder_level,
                          description = :description,
                          supplier_id = :supplier_id
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':item_name', $data['item_name']);
            $stmt->bindParam(':sku', $data['sku']);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':quantity', $data['quantity']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':reorder_level', $data['reorder_level']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':supplier_id', $supplier_id);
            
            $stmt->execute();
            
            // Record stock movement if quantity changed
            if ($current_item['quantity'] != $data['quantity']) {
                $change = $data['quantity'] - $current_item['quantity'];
                $movement_type = $change > 0 ? 'in' : 'out';
                $this->recordStockMovement(
                    $id, 
                    $movement_type, 
                    abs($change), 
                    $current_item['quantity'], 
                    $data['quantity'],
                    'Stock adjustment'
                );
            }
            
            // Record price change if price changed
            if ($current_item['price'] != $data['price']) {
                $this->recordPriceChange($id, $current_item['price'], $data['price']);
            }
            
            // Update category item counts
            if ($current_item['category_id'] != $category_id) {
                $this->updateCategoryItemCount($current_item['category_id']);
                $this->updateCategoryItemCount($category_id);
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Item updated successfully'];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Error updating item: ' . $e->getMessage()];
        }
    }
    
    // Delete inventory item
    public function deleteItem($id) {
        try {
            $this->conn->beginTransaction();
            
            // Get item data for category count update
            $item = $this->getItemById($id);
            
            if (!$item) {
                throw new Exception("Item not found");
            }
            
            // Delete item
            $query = "DELETE FROM inventory_items WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Update category item count
            if ($item && isset($item['category_id'])) {
                $this->updateCategoryItemCount($item['category_id']);
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Item deleted successfully'];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Error deleting item: ' . $e->getMessage()];
        }
    }
    
    // Get all categories
    public function getCategories() {
        $query = "SELECT * FROM categories ORDER BY category_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get all suppliers
    public function getSuppliers() {
        $query = "SELECT * FROM suppliers ORDER BY supplier_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get low stock items
    public function getLowStockItems() {
        $query = "SELECT 
                    i.id,
                    i.item_name,
                    i.sku,
                    i.quantity,
                    i.reorder_level,
                    (i.reorder_level - i.quantity) as needed_quantity,
                    s.supplier_name,
                    s.email as supplier_email,
                    s.phone as supplier_phone
                  FROM inventory_items i
                  LEFT JOIN suppliers s ON i.supplier_id = s.id
                  WHERE i.quantity <= i.reorder_level AND i.quantity > 0
                  ORDER BY i.quantity ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get stock movement history for an item
    public function getStockMovementHistory($item_id) {
        $query = "SELECT * FROM stock_movements 
                  WHERE item_id = :item_id 
                  ORDER BY created_at DESC 
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Export inventory data
    public function exportInventory($format = 'csv') {
        $items = $this->getInventoryItems(1, 1000, 'all', null);
        
        if ($format === 'csv') {
            $output = fopen('php://temp', 'w');
            fputcsv($output, ['ID', 'Item Name', 'SKU', 'Category', 'Quantity', 'Price', 'Status', 'Supplier', 'Last Updated']);
            
            foreach ($items as $item) {
                fputcsv($output, [
                    $item['id'],
                    $item['item_name'],
                    $item['sku'],
                    $item['category_name'],
                    $item['quantity'],
                    $item['price'],
                    $item['status'],
                    $item['supplier_name'],
                    $item['last_updated']
                ]);
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            return $csv;
        }
        
        return json_encode($items);
    }
    
    // Helper function: Check if SKU exists
    private function skuExists($sku, $exclude_id = null) {
        $query = "SELECT id FROM inventory_items WHERE sku = :sku";
        if ($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sku', $sku);
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Helper function: Get or create category
    private function getOrCreateCategory($category_name) {
        // Check if category exists
        $query = "SELECT id FROM categories WHERE category_name = :name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $category_name);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        
        // Create new category with default color
        $colors = ['blue', 'emerald', 'purple', 'amber', 'rose'];
        $random_color = $colors[array_rand($colors)];
        
        $query = "INSERT INTO categories (category_name, color_class) VALUES (:name, :color)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':color', $random_color);
        $stmt->execute();
        
        return $this->conn->lastInsertId();
    }
    
    // Helper function: Get or create supplier
    private function getOrCreateSupplier($supplier_name) {
        // Check if supplier exists
        $query = "SELECT id FROM suppliers WHERE supplier_name = :name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $supplier_name);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        
        // Create new supplier
        $query = "INSERT INTO suppliers (supplier_name) VALUES (:name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $supplier_name);
        $stmt->execute();
        
        return $this->conn->lastInsertId();
    }
    
    // Helper function: Record stock movement
    private function recordStockMovement($item_id, $type, $quantity_change, $previous_qty, $new_qty, $notes = '') {
        $query = "INSERT INTO stock_movements 
                  (item_id, movement_type, quantity_change, previous_quantity, new_quantity, notes) 
                  VALUES 
                  (:item_id, :type, :quantity_change, :previous_qty, :new_qty, :notes)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':quantity_change', $quantity_change);
        $stmt->bindParam(':previous_qty', $previous_qty);
        $stmt->bindParam(':new_qty', $new_qty);
        $stmt->bindParam(':notes', $notes);
        
        return $stmt->execute();
    }
    
    // Helper function: Record price change
    private function recordPriceChange($item_id, $old_price, $new_price) {
        $query = "INSERT INTO price_history (item_id, old_price, new_price) 
                  VALUES (:item_id, :old_price, :new_price)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':old_price', $old_price);
        $stmt->bindParam(':new_price', $new_price);
        
        return $stmt->execute();
    }
    
    // Helper function: Update category item count
    private function updateCategoryItemCount($category_id) {
        $query = "UPDATE categories c 
                  SET item_count = (
                      SELECT COUNT(*) 
                      FROM inventory_items i 
                      WHERE i.category_id = c.id
                  )
                  WHERE c.id = :category_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        return $stmt->execute();
    }
    
    // Add this method to your InventoryManager class in inventory-function.php
public function addCategory($category_name, $color = 'blue') {
    try {
        // Validate input
        if (empty($category_name)) {
            return ['success' => false, 'message' => 'Category name is required'];
        }
        
        // Check if category already exists
        $checkQuery = "SELECT id FROM categories WHERE category_name = :name";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':name', $category_name);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Category already exists'];
        }
        
        // Insert new category
        $query = "INSERT INTO categories (category_name, color_class, item_count) VALUES (:name, :color, 0)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $category_name);
        $stmt->bindParam(':color', $color);
        $stmt->execute();
        
        return [
            'success' => true, 
            'message' => 'Category added successfully',
            'id' => $this->conn->lastInsertId()
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error adding category: ' . $e->getMessage()];
    }
}
    // Search items
    public function searchItems($search_term) {
        $query = "SELECT 
                    i.*,
                    c.category_name,
                    c.color_class,
                    s.supplier_name,
                    CASE 
                        WHEN i.quantity <= 0 THEN 'out_of_stock'
                        WHEN i.quantity <= i.reorder_level THEN 'low_stock'
                        ELSE 'in_stock'
                    END as status
                  FROM inventory_items i
                  LEFT JOIN categories c ON i.category_id = c.id
                  LEFT JOIN suppliers s ON i.supplier_id = s.id
                  WHERE i.item_name LIKE :search 
                     OR i.sku LIKE :search
                     OR i.description LIKE :search
                     OR c.category_name LIKE :search
                     OR s.supplier_name LIKE :search
                  ORDER BY i.item_name ASC
                  LIMIT 20";
        
        $search_term = "%{$search_term}%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':search', $search_term);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Bulk update stock
    public function bulkUpdateStock($updates) {
        try {
            $this->conn->beginTransaction();
            
            foreach ($updates as $update) {
                $current_item = $this->getItemById($update['id']);
                
                if (!$current_item) {
                    throw new Exception("Item with ID {$update['id']} not found");
                }
                
                $new_quantity = $current_item['quantity'] + $update['quantity_change'];
                
                // Prevent negative quantity
                if ($new_quantity < 0) {
                    throw new Exception("Cannot reduce quantity below 0 for item {$current_item['item_name']}");
                }
                
                $query = "UPDATE inventory_items SET quantity = :quantity WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':quantity', $new_quantity);
                $stmt->bindParam(':id', $update['id']);
                $stmt->execute();
                
                $movement_type = $update['quantity_change'] > 0 ? 'in' : 'out';
                $this->recordStockMovement(
                    $update['id'],
                    $movement_type,
                    abs($update['quantity_change']),
                    $current_item['quantity'],
                    $new_quantity,
                    $update['notes'] ?? 'Bulk update'
                );
            }
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Bulk update completed'];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => 'Error in bulk update: ' . $e->getMessage()];
        }
    }
}
?>