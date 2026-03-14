// assets/js/pages/orders.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('Orders.js loaded');
    setupEventListeners();
    
    // Set active tab based on URL
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status') || 'pending';
    setActiveTab(status);
    
    // Set filter dropdown values based on URL
    const filterStatus = urlParams.get('filter_status');
    if (filterStatus) {
        const statusFilter = document.getElementById('poStatus');
        if (statusFilter) statusFilter.value = filterStatus;
    }
    
    const supplier = urlParams.get('supplier');
    if (supplier && supplier !== 'all') {
        const supplierFilter = document.getElementById('poSupplier');
        if (supplierFilter) supplierFilter.value = supplier;
    }
});

function setActiveTab(status) {
    document.querySelectorAll('.tab').forEach(tab => {
        if (tab.dataset.tab === status) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
}

function setupEventListeners() {
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const status = this.dataset.tab;
            window.location.href = `?status=${status}`;
        });
    });
    
    // Status filter
    const statusFilter = document.getElementById('poStatus');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterByStatus(this.value);
        });
    }
    
    // Supplier filter
    const supplierFilter = document.getElementById('poSupplier');
    if (supplierFilter) {
        supplierFilter.addEventListener('change', function() {
            filterBySupplier(this.value);
        });
    }
    
    // Search with debounce
    const searchInput = document.getElementById('searchPO');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterBySearch(this.value);
            }, 500);
        });
        
        // Enter key for search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterBySearch(this.value);
            }
        });
    }
}

function filterByStatus(status) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('status') || 'pending';
    const supplier = urlParams.get('supplier') || 'all';
    const search = urlParams.get('search') || '';
    
    let url = `?status=${currentTab}&filter_status=${status}`;
    if (supplier !== 'all') url += `&supplier=${supplier}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    
    window.location.href = url;
}

function filterBySupplier(supplierId) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('status') || 'pending';
    const statusFilter = urlParams.get('filter_status') || 'all';
    const search = urlParams.get('search') || '';
    
    let url = `?status=${currentTab}&filter_status=${statusFilter}`;
    if (supplierId !== 'all') url += `&supplier=${supplierId}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    
    window.location.href = url;
}

function filterBySearch(searchTerm) {
    if (!searchTerm.trim()) {
        // If search is empty, remove it from URL
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('search');
        window.location.href = '?' + urlParams.toString();
        return;
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('status') || 'pending';
    const statusFilter = urlParams.get('filter_status') || 'all';
    const supplier = urlParams.get('supplier') || 'all';
    
    let url = `?status=${currentTab}&filter_status=${statusFilter}&search=${encodeURIComponent(searchTerm)}`;
    if (supplier !== 'all') url += `&supplier=${supplier}`;
    
    window.location.href = url;
}

function clearFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('status') || 'pending';
    window.location.href = `?status=${currentTab}`;
}

// Update PO Status function
async function updatePOStatus(poId, newStatus) {
    console.log(`Updating PO ${poId} to ${newStatus}...`);
    
    let actionText = newStatus;
    if (newStatus === 'approved') actionText = 'approve';
    else if (newStatus === 'rejected') actionText = 'reject';
    else if (newStatus === 'completed') actionText = 'mark as completed';
    else if (newStatus === 'cancelled') actionText = 'cancel';
    
    if (!confirm(`Are you sure you want to ${actionText} this purchase order?`)) {
        return;
    }
    
    try {
        const response = await fetch('../api/update_po_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                po_id: poId,
                status: newStatus
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Server response:', result);
        
        if (result.success) {
            alert(`Purchase order ${newStatus} successfully!`);
            window.location.reload();
        } else {
            alert('Error updating PO: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error updating PO:', error);
        alert('Error updating purchase order: ' + error.message);
    }
}

// Modal functions
function openPOModal() {
    console.log('Opening modal...');
    document.getElementById('poModal').classList.remove('modal-hidden');
    document.getElementById('poModal').classList.add('modal-visible');
    
    // Load data after modal is visible
    setTimeout(() => {
        loadSuppliersForModal();
        loadItemsForModal();
    }, 200);
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('modal-visible');
    document.getElementById(modalId).classList.add('modal-hidden');
}

async function loadSuppliersForModal() {
    try {
        console.log('Fetching suppliers...');
        const response = await fetch('../api/get_suppliers.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const suppliers = await response.json();
        console.log('Suppliers received:', suppliers);
        
        // Find the supplier select in the modal
        const select = document.querySelector('#poModal select.form-select:first-of-type');
        
        if (!select) {
            console.error('❌ Supplier select not found!');
            return;
        }
        
        select.innerHTML = '<option value="">Select Supplier</option>' +
            suppliers.map(s => `<option value="${s.id}">${s.supplier_name}</option>`).join('');
        
        console.log('✅ Supplier select updated with', suppliers.length, 'suppliers');
    } catch (error) {
        console.error('❌ Error loading suppliers:', error);
    }
}

async function loadItemsForModal() {
    try {
        console.log('Fetching items...');
        const response = await fetch('../api/get_inventory_items.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const items = await response.json();
        console.log('Items received:', items);
        window.inventoryItems = items;
        console.log('✅ Items stored globally');
    } catch (error) {
        console.error('❌ Error loading items:', error);
    }
}

function addItem() {
    const tbody = document.querySelector('.items-table tbody');
    if (!tbody) {
        console.error('Items table body not found!');
        return;
    }
    
    const row = document.createElement('tr');
    
    row.innerHTML = `
        <td>
            <select class="item-select" onchange="updateItemPrice(this)">
                <option value="">Select Item</option>
                ${window.inventoryItems?.map(item => 
                    `<option value="${item.id}" data-price="${item.price}">${item.item_name} (${item.sku})</option>`
                ).join('') || '<option value="" disabled>No items available</option>'}
            </select>
        </td>
        <td><input type="number" class="item-qty" value="1" min="1" onchange="calculateItemTotal(this)"></td>
        <td><input type="number" class="item-price" value="0" step="0.01" onchange="calculateItemTotal(this)"></td>
        <td class="item-total">₱0.00</td>
        <td><button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
    `;
    
    tbody.appendChild(row);
    console.log('✅ New item row added');
}

function updateItemPrice(select) {
    const selected = select.options[select.selectedIndex];
    const price = selected.dataset.price || 0;
    const row = select.closest('tr');
    row.querySelector('.item-price').value = price;
    calculateItemTotal(row.querySelector('.item-price'));
}

function calculateItemTotal(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const total = qty * price;
    
    row.querySelector('.item-total').textContent = '₱' + total.toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-total').forEach(cell => {
        subtotal += parseFloat(cell.textContent.replace('₱', '')) || 0;
    });
    
    const tax = subtotal * 0.12; // 10% tax
    const total = subtotal + tax;
    
    // Update the display
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
    
    console.log('Totals calculated:', {subtotal, tax, total});
    
    return {subtotal, tax, total};
}

function removeItem(button) {
    if (document.querySelectorAll('.items-table tbody tr').length > 1) {
        button.closest('tr').remove();
        calculateGrandTotal();
    } else {
        alert('You must have at least one item');
    }
}

async function savePO() {
    console.log('Saving PO...');
    
    // Get form values using the correct IDs
    const supplier = document.getElementById('modalSupplierSelect').value;
    const orderDate = document.getElementById('orderDate').value;
    const priority = document.getElementById('priority').value;
    const deliveryDate = document.getElementById('expectedDelivery').value;
    const description = document.getElementById('poDescription').value;
    const shippingAddress = document.getElementById('shippingAddress').value;
    const additionalNotes = document.getElementById('additionalNotes').value;
    const department = document.getElementById('modalDepartmentSelect').value;
    
    // Validate required fields
    if (!supplier) {
        alert('Please select a supplier');
        return;
    }
    
    if (!orderDate) {
        alert('Please select an order date');
        return;
    }
    
    // Get items from the table
    const items = [];
    const itemRows = document.querySelectorAll('.items-table tbody tr');
    
    if (itemRows.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    itemRows.forEach((row, index) => {
        const itemSelect = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.item-qty');
        const priceInput = row.querySelector('.item-price');
        
        if (itemSelect && itemSelect.value) {
            items.push({
                item_id: itemSelect.value,
                quantity: parseFloat(qtyInput?.value) || 0,
                unit_price: parseFloat(priceInput?.value) || 0,
                total_price: parseFloat(row.querySelector('.item-total')?.textContent.replace('₱', '')) || 0
            });
            console.log(`Item ${index + 1}:`, items[items.length - 1]);
        }
    });
    
    if (items.length === 0) {
        alert('Please select at least one item');
        return;
    }
    
    // Calculate totals
    const totals = calculateGrandTotal();
    
    // Combine notes
    const combinedNotes = [description, additionalNotes].filter(n => n).join('\n');
    
    const poData = {
        supplier_id: supplier,
        order_date: orderDate,
        expected_delivery: deliveryDate || null,
        priority: priority,
        department: department,
        subtotal: totals.subtotal,
        tax_amount: totals.tax,
        shipping_cost: 0,
        total_amount: totals.total,
        notes: combinedNotes,
        items: items
    };
    
    console.log('Sending PO data:', poData);
    
    try {
        const response = await fetch('../api/create_purchase_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(poData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Server response:', result);
        
        if (result.success) {
            alert(`PO ${result.po_number} created successfully!`);
            closeModal('poModal');
            // Reset form
            document.getElementById('poForm').reset();
            // Clear items table
            const tbody = document.querySelector('.items-table tbody');
            tbody.innerHTML = '';
            // Add one empty row
            addItem();
            // Reload the page to show new PO
            window.location.reload();
        } else {
            alert('Error creating PO: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving PO:', error);
        alert('Error saving purchase order: ' + error.message);
    }
}

// View PO function
function viewPO(poId) {
    console.log('Viewing PO:', poId);
    // You can implement this to show PO details
    alert('View PO feature coming soon!');
}