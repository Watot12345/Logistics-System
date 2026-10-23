<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Users
        $users = [
            [
                'id' => 1,
                'employee_id' => 'EMP5675',
                'username' => 'asierra389@gmail.com',
                'name' => 'Joshua sierra',
                'full_name' => 'Joshua sierra',
                'email' => 'asierra389@gmail.com',
                'password' => Hash::make('Joshua123'),
                'role' => 'employee',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'employee_id' => 'EMP4112',
                'username' => 'joshua',
                'name' => 'Joshua arncel sierra',
                'full_name' => 'Joshua arncel sierra',
                'email' => 'asierr389@gmail.com',
                'password' => Hash::make('joshua'),
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'id' => 3,
                'employee_id' => 'EMP0332',
                'username' => 'jon jon',
                'name' => 'jon jon delecruz',
                'full_name' => 'jon jon delecruz',
                'email' => 'jonjon@gmail.com',
                'password' => Hash::make('jonjon123'),
                'role' => 'dispatcher',
                'status' => 'active',
            ],
            [
                'id' => 4,
                'employee_id' => 'EMP1455',
                'username' => 'jay jay',
                'name' => 'jay jay dela santos',
                'full_name' => 'jay jay dela santos',
                'email' => 'jayjay@gmail.com',
                'password' => Hash::make('jayjay123'),
                'role' => 'fleet_manager',
                'status' => 'active',
            ],
            [
                'id' => 5,
                'employee_id' => 'EMP2401',
                'username' => 'john john',
                'name' => 'john john santos',
                'full_name' => 'john john santos',
                'email' => 'johnjohn@gmail.com',
                'password' => Hash::make('johnjohn123'),
                'role' => 'driver',
                'status' => 'active',
            ],
            [
                'id' => 6,
                'employee_id' => 'EMP6305',
                'username' => 'john doe',
                'name' => 'john doe',
                'full_name' => 'john doe',
                'email' => 'johndoe@gmail.com',
                'password' => Hash::make('johndoe123'),
                'role' => 'mechanic',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            DB::table('users')->updateOrInsert(['id' => $userData['id']], $userData);
        }

        // 2. Assets (Vehicles)
        $assets = [
            ['id' => 25, 'asset_name' => 'HINO FM1A L7D', 'asset_type' => 'vehicle', 'status' => 'good', 'asset_condition' => 75],
            ['id' => 26, 'asset_name' => 'L-300', 'asset_type' => 'vehicle', 'status' => 'good', 'asset_condition' => 70],
            ['id' => 27, 'asset_name' => 'Isuzu Forward NQR', 'asset_type' => 'vehicle', 'status' => 'good', 'asset_condition' => 85],
            ['id' => 28, 'asset_name' => 'Fuso Canter FE71', 'asset_type' => 'vehicle', 'status' => 'warning', 'asset_condition' => 60],
        ];

        foreach ($assets as $asset) {
            DB::table('assets')->updateOrInsert(['id' => $asset['id']], $asset);
        }

        // 3. Categories
        $categories = [
            ['id' => 1, 'category_name' => 'Electronics', 'description' => 'Electronic components and devices', 'color_class' => 'blue', 'item_count' => 0],
            ['id' => 2, 'category_name' => 'Furniture', 'description' => 'Office and warehouse furniture', 'color_class' => 'emerald', 'item_count' => 0],
            ['id' => 3, 'category_name' => 'Clothing & Uniforms', 'description' => 'Workwear and branded uniforms', 'color_class' => 'purple', 'item_count' => 3],
            ['id' => 4, 'category_name' => 'Automotive Parts', 'description' => 'Fleet maintenance supplies', 'color_class' => 'amber', 'item_count' => 2],
            ['id' => 5, 'category_name' => 'Packaging Materials', 'description' => 'Boxes, tape, bubble wrap', 'color_class' => 'indigo', 'item_count' => 1],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(['id' => $cat['id']], $cat);
        }

        // 4. Suppliers
        $suppliers = [
            ['id' => 1, 'supplier_name' => 'ABC Supplies Inc.', 'contact_person' => 'John Smith', 'email' => 'john@abcsupplies.com', 'phone' => '+1-555-0101', 'address' => '123 Business Ave, New York, NY 10001'],
            ['id' => 2, 'supplier_name' => 'Global Traders Ltd.', 'contact_person' => 'Sarah Johnson', 'email' => 'sarah@globaltraders.com', 'phone' => '+1-555-0102', 'address' => '456 Commerce St, Los Angeles, CA 90001'],
            ['id' => 3, 'supplier_name' => 'Direct Source Co.', 'contact_person' => 'Mike Wilson', 'email' => 'mike@directsource.com', 'phone' => '+1-555-0103', 'address' => '789 Industrial Rd, Chicago, IL 60601'],
            ['id' => 4, 'supplier_name' => 'Quality Distributors', 'contact_person' => 'Emily Brown', 'email' => 'emily@qualitydist.com', 'phone' => '+1-555-0104', 'address' => '321 Warehouse Blvd, Houston, TX 77001'],
        ];

        foreach ($suppliers as $sup) {
            DB::table('suppliers')->updateOrInsert(['id' => $sup['id']], $sup);
        }

        // 5. Inventory Items
        $items = [
            ['id' => 1, 'item_name' => 'High-Visibility Safety Vest', 'sku' => 'VEST-HV-01', 'category_id' => 3, 'quantity' => 45, 'price' => 150.00, 'reorder_level' => 10, 'supplier_id' => 1, 'status' => 'in_stock'],
            ['id' => 2, 'item_name' => 'Industrial Heavy-Duty Gloves', 'sku' => 'GLV-IND-02', 'category_id' => 3, 'quantity' => 5, 'price' => 120.00, 'reorder_level' => 10, 'supplier_id' => 4, 'status' => 'low_stock'],
            ['id' => 3, 'item_name' => 'Heavy Duty Brake Fluid (1L)', 'sku' => 'FL-BRK-01', 'category_id' => 4, 'quantity' => 0, 'price' => 350.00, 'reorder_level' => 5, 'supplier_id' => 2, 'status' => 'out_of_stock'],
            ['id' => 4, 'item_name' => 'Cardboard Shipping Box (Large)', 'sku' => 'BOX-LG-100', 'category_id' => 5, 'quantity' => 120, 'price' => 45.00, 'reorder_level' => 20, 'supplier_id' => 3, 'status' => 'in_stock'],
        ];

        foreach ($items as $item) {
            DB::table('inventory_items')->updateOrInsert(['id' => $item['id']], $item);
        }

        // 6. Maintenance Alerts
        $alerts = [
            [
                'id' => 1,
                'asset_name' => 'HINO FM1A L7D',
                'issue' => 'Brake Pad Replacement & Inspection',
                'issue_type' => 'major',
                'priority' => 'high',
                'assigned_mechanic' => 6,
                'estimated_hours' => 3.5,
                'due_date' => date('Y-m-d', strtotime('+3 days')),
                'status' => 'pending',
                'created_by' => 4,
            ],
            [
                'id' => 2,
                'asset_name' => 'Fuso Canter FE71',
                'issue' => 'Oil Filter and Engine Oil Change',
                'issue_type' => 'minor',
                'priority' => 'medium',
                'assigned_mechanic' => 6,
                'estimated_hours' => 1.5,
                'due_date' => date('Y-m-d', strtotime('+5 days')),
                'status' => 'in_progress',
                'created_by' => 4,
            ],
        ];

        foreach ($alerts as $alert) {
            DB::table('maintenance_alerts')->updateOrInsert(['id' => $alert['id']], $alert);
        }

        // 7. Shipments
        $shipments = [
            [
                'shipment_id' => 101,
                'customer_name' => 'Metro Retail Corp',
                'delivery_address' => 'Warehouse 4, North Port Road, Manila',
                'order_id' => 1001,
                'vehicle_id' => 25,
                'driver_id' => 5,
                'shipment_status' => 'in_transit',
                'departure_time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'estimated_arrival' => date('Y-m-d H:i:s', strtotime('+3 hours')),
                'current_location' => 'EDSA Balintawak Bypass',
            ],
            [
                'shipment_id' => 102,
                'customer_name' => 'Apex Manufacturing Inc.',
                'delivery_address' => 'Industrial Park 2, Calamba, Laguna',
                'order_id' => 1002,
                'vehicle_id' => 26,
                'driver_id' => 5,
                'shipment_status' => 'pending',
                'departure_time' => date('Y-m-d H:i:s', strtotime('+4 hours')),
                'estimated_arrival' => date('Y-m-d H:i:s', strtotime('+8 hours')),
                'current_location' => 'Main Distribution Center',
            ],
        ];

        foreach ($shipments as $sh) {
            DB::table('shipments')->updateOrInsert(['shipment_id' => $sh['shipment_id']], $sh);
        }
    }
}
