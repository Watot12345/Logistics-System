<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Assets Table (Vehicles, equipment, etc.)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name');
            $table->string('asset_type')->default('vehicle'); // vehicle, equipment, warehouse, other
            $table->string('status')->default('good'); // good, warning, bad
            $table->integer('asset_condition')->default(100);
            $table->timestamps();
        });

        // 2. Documents
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('document_type');
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expiry_date')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
        });

        // 3. User Activity Logs
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('action_type', 50);
            $table->dateTime('timestamp')->useCurrent();
            $table->timestamps();
        });

        // 4. Maintenance Alerts
        Schema::create('maintenance_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name');
            $table->string('issue');
            $table->string('issue_type')->default('minor'); // minor, major, critical
            $table->string('priority')->default('medium'); // low, medium, high
            $table->foreignId('assigned_mechanic')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('estimated_hours', 5, 2)->nullable();
            $table->date('due_date');
            $table->dateTime('started_at')->nullable();
            $table->date('completed_date')->nullable();
            $table->text('completed_notes')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('color_class', 50)->nullable();
            $table->integer('item_count')->default(0);
            $table->timestamps();
        });

        // 6. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 7. Inventory Items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('sku', 50)->unique();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('reorder_level')->default(10);
            $table->text('description')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('status')->default('in_stock'); // in_stock, low_stock, out_of_stock
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
        });

        // 8. Stock Movements
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('movement_type'); // in, out, adjustment
            $table->integer('quantity_change');
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 9. Price History
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });

        // 10. Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('order_date');
            $table->date('expected_delivery')->nullable();
            $table->date('actual_delivery')->nullable();
            $table->string('status')->default('draft'); // draft, pending, approved, rejected, completed, cancelled
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 11. Purchase Order Items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->integer('received_quantity')->default(0);
            $table->string('status')->default('pending'); // pending, partial, received, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 12. Receiving History
        Schema::create('receiving_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->integer('quantity_received');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('received_at')->useCurrent();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 13. Vehicle Reservations
        Schema::create('vehicle_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('customer_name')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('department', 100)->nullable();
            $table->text('purpose')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, completed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 14. Dispatch Schedule
        Schema::create('dispatch_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained('vehicle_reservations')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_date');
            $table->string('shift')->default('morning'); // morning, afternoon, night
            $table->string('status')->default('scheduled'); // scheduled, in-progress, delivered, awaiting_verification, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 15. Shipments
        Schema::create('shipments', function (Blueprint $table) {
            $table->id('shipment_id');
            $table->string('customer_name')->nullable();
            $table->text('delivery_address')->nullable();
            $table->integer('order_id')->nullable();
            $table->foreignId('vehicle_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('shipment_status')->default('pending'); // pending, in_transit, delivered, delayed
            $table->dateTime('departure_time')->nullable();
            $table->dateTime('estimated_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->string('current_location')->nullable();
            $table->timestamps();
        });

        // 16. Shipment Tracking
        Schema::create('shipment_tracking', function (Blueprint $table) {
            $table->id('tracking_id');
            $table->unsignedBigInteger('shipment_id');
            $table->foreign('shipment_id')->references('shipment_id')->on('shipments')->cascadeOnDelete();
            $table->string('location')->nullable();
            $table->string('status_update')->nullable();
            $table->timestamps();
        });

        // 17. Shipment Delays
        Schema::create('shipment_delays', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_id', 50)->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('route_name')->nullable();
            $table->string('delay_reason')->nullable();
            $table->string('delay_minutes', 50)->nullable();
            $table->string('delay_duration', 50)->nullable();
            $table->string('delay_type')->default('other'); // traffic, weather, mechanical, loading, accident, other
            $table->dateTime('reported_at')->useCurrent();
            $table->string('delay_unit')->default('minutes');
            $table->timestamps();
        });

        // 18. Login Verifications (from includes/auth.php)
        Schema::create('login_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 10)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_verifications');
        Schema::dropIfExists('shipment_delays');
        Schema::dropIfExists('shipment_tracking');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('dispatch_schedule');
        Schema::dropIfExists('vehicle_reservations');
        Schema::dropIfExists('receiving_history');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('maintenance_alerts');
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('assets');
    }
};
