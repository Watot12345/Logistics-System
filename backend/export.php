<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once 'inventory-function.php';

$inventory = new InventoryManager();
$format = $_GET['format'] ?? 'csv';

if ($format === 'csv') {
    // Get inventory items
    $items = $inventory->getInventoryItems(1, 10000, 'all', null);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, [
        'ID',
        'Item Name',
        'SKU',
        'Category',
        'Quantity',
        'Price',
        'Status',
        'Supplier',
        'Description',
        'Reorder Level',
        'Last Updated'
    ]);
    
    // Add data rows
    foreach ($items as $item) {
        // Determine status
        $status = match(true) {
            $item['quantity'] <= 0 => 'Out of Stock',
            $item['quantity'] <= $item['reorder_level'] => 'Low Stock',
            default => 'In Stock'
        };
        
        fputcsv($output, [
            $item['id'],
            $item['item_name'],
            $item['sku'],
            $item['category_name'] ?? 'Uncategorized',
            $item['quantity'],
            '$' . number_format($item['price'], 2),
            $status,
            $item['supplier_name'] ?? 'No Supplier',
            $item['description'] ?? '',
            $item['reorder_level'],
            date('Y-m-d H:i:s', strtotime($item['last_updated']))
        ]);
    }
    
    fclose($output);
    exit();
    
} elseif ($format === 'excel') {
    // For Excel XML format (more compatible with Excel)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.xls"');
    
    $items = $inventory->getInventoryItems(1, 10000, 'all', null);
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Item Name</th>';
    echo '<th>SKU</th>';
    echo '<th>Category</th>';
    echo '<th>Quantity</th>';
    echo '<th>Price</th>';
    echo '<th>Status</th>';
    echo '<th>Supplier</th>';
    echo '<th>Description</th>';
    echo '<th>Reorder Level</th>';
    echo '<th>Last Updated</th>';
    echo '</tr>';
    
    foreach ($items as $item) {
        $status = match(true) {
            $item['quantity'] <= 0 => 'Out of Stock',
            $item['quantity'] <= $item['reorder_level'] => 'Low Stock',
            default => 'In Stock'
        };
        
        echo '<tr>';
        echo '<td>' . $item['id'] . '</td>';
        echo '<td>' . htmlspecialchars($item['item_name']) . '</td>';
        echo '<td>' . htmlspecialchars($item['sku']) . '</td>';
        echo '<td>' . htmlspecialchars($item['category_name'] ?? 'Uncategorized') . '</td>';
        echo '<td>' . $item['quantity'] . '</td>';
        echo '<td>$' . number_format($item['price'], 2) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '<td>' . htmlspecialchars($item['supplier_name'] ?? 'No Supplier') . '</td>';
        echo '<td>' . htmlspecialchars($item['description'] ?? '') . '</td>';
        echo '<td>' . $item['reorder_level'] . '</td>';
        echo '<td>' . date('Y-m-d H:i:s', strtotime($item['last_updated'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit();
    
} elseif ($format === 'json') {
    // JSON export
    $items = $inventory->getInventoryItems(1, 10000, 'all', null);
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.json"');
    
    echo json_encode($items, JSON_PRETTY_PRINT);
    exit();
    
} elseif ($format === 'pdf') {
    // For PDF, you'd need a library like TCPDF or Dompdf
    // This is a simple HTML version that can be printed as PDF
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d') . '.html"');
    
    $items = $inventory->getInventoryItems(1, 10000, 'all', null);
    $stats = $inventory->getDashboardStats();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Inventory Export</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th { background: #2563eb; color: white; padding: 10px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) { background: #f9f9f9; }
            .summary { margin-bottom: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px; }
            .summary-item { margin-right: 20px; display: inline-block; }
            .label { font-weight: bold; color: #666; }
            .value { font-size: 18px; color: #333; }
            .status-in-stock { color: #10b981; }
            .status-low-stock { color: #f59e0b; }
            .status-out-of-stock { color: #ef4444; }
        </style>
    </head>
    <body>
        <h1>Inventory Export - <?php echo date('F d, Y'); ?></h1>
        
        <div class="summary">
            <div class="summary-item">
                <span class="label">Total Items:</span>
                <span class="value"><?php echo $stats['total_items'] ?? 0; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Categories:</span>
                <span class="value"><?php echo $stats['total_categories'] ?? 0; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Low Stock:</span>
                <span class="value"><?php echo $stats['low_stock_items'] ?? 0; ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Total Value:</span>
                <span class="value">$<?php echo number_format($stats['total_inventory_value'] ?? 0, 2); ?></span>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Supplier</th>
                    <th>Reorder Level</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    $status_class = match(true) {
                        $item['quantity'] <= 0 => 'status-out-of-stock',
                        $item['quantity'] <= $item['reorder_level'] => 'status-low-stock',
                        default => 'status-in-stock'
                    };
                    $status_text = match(true) {
                        $item['quantity'] <= 0 => 'Out of Stock',
                        $item['quantity'] <= $item['reorder_level'] => 'Low Stock',
                        default => 'In Stock'
                    };
                ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                    <td><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
                    <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'No Supplier'); ?></td>
                    <td><?php echo $item['reorder_level']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px; color: #666; font-size: 12px;">
            Generated on: <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </body>
    </html>
    <?php
    exit();
    
} else {
    // Default to CSV
    header("Location: export.php?format=csv");
    exit();
}
?>