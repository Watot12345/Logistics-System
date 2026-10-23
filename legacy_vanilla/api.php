<?php
// api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once 'inventory-functions.php';

$inventory = new InventoryManager();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_dashboard_stats':
        echo json_encode($inventory->getDashboardStats());
        break;
        
    case 'get_items':
        $page = $_GET['page'] ?? 1;
        $filter = $_GET['filter'] ?? 'all';
        $category = $_GET['category'] ?? null;
        
        $items = $inventory->getInventoryItems($page, 10, $filter, $category);
        $total = $inventory->getTotalCount($filter, $category);
        
        echo json_encode([
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / 10)
        ]);
        break;
        
    case 'get_item':
        $id = $_GET['id'] ?? 0;
        echo json_encode($inventory->getItemById($id));
        break;
        
    case 'add_item':
        echo json_encode($inventory->addItem($_POST));
        break;
        
    case 'update_item':
        $id = $_POST['id'] ?? 0;
        echo json_encode($inventory->updateItem($id, $_POST));
        break;
        
    case 'delete_item':
        $id = $_POST['id'] ?? 0;
        echo json_encode($inventory->deleteItem($id));
        break;
        
    case 'get_categories':
        echo json_encode($inventory->getCategories());
        break;
        
    case 'get_suppliers':
        echo json_encode($inventory->getSuppliers());
        break;
        
    case 'get_low_stock':
        echo json_encode($inventory->getLowStockItems());
        break;
        
    case 'search':
        $term = $_GET['term'] ?? '';
        echo json_encode($inventory->searchItems($term));
        break;
        
    case 'export':
        $format = $_GET['format'] ?? 'csv';
        $data = $inventory->exportInventory($format);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="inventory_export.csv"');
            echo $data;
        } else {
            echo $data;
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>