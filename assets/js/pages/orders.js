// assets/js/pages/orders.js

// Global variables
let categories = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('Orders.js loaded');
    setupEventListeners();
    loadCategories(); // Load categories for new item form
    
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

// Load categories for new item form
async function loadCategories() {
    try {
        const response = await fetch('../api/get_categories.php');
        if (response.ok) {
            categories = await response.json();
            console.log('Categories loaded:', categories);
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        categories = [];
    }
}

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
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterBySearch(this.value);
            }
        });
    }
    
    // Category selection for new item form
    const categorySelect = document.getElementById('new_product_category');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value === 'new') {
                document.getElementById('newCategoryInput').style.display = 'block';
            } else {
                document.getElementById('newCategoryInput').style.display = 'none';
            }
        });
    }
}

// ===== FILTER FUNCTIONS =====
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

// ===== SEARCH FUNCTION - THIS IS WHAT YOU NEED =====
function filterBySearch(searchTerm) {
    console.log('Searching for:', searchTerm);
    
    if (!searchTerm.trim()) {
        // Remove search from URL
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('search');
        window.location.href = '?' + urlParams.toString();
        return;
    }
    
    // Get current tab
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('status') || 'pending';
    
    // Redirect with search parameter
    window.location.href = `?status=${currentTab}&search=${encodeURIComponent(searchTerm)}`;
}

// ===== SETUP ON PAGE LOAD =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up event listeners...');
    
    // Setup search
    const searchInput = document.getElementById('searchPO');
    if (searchInput) {
        console.log('Search input found');
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                filterBySearch(this.value);
            }, 500);
        });
    } else {
        console.error('Search input not found!');
    }
    
    // Set active tab
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status') || 'pending';
    document.querySelectorAll('.tab').forEach(tab => {
        if (tab.dataset.tab === status) {
            tab.classList.add('active');
        }
    });
});



// Update the search input event listener
document.addEventListener('DOMContentLoaded', function() {
    console.log('Orders.js loaded');
    setupEventListeners();
    
    // Set active tab based on URL
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status') || 'pending';
    setActiveTab(status);
    
    // Add search event listener with proper handling
    const searchInput = document.getElementById('searchPO');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterBySearch(this.value);
            }, 500);
        });
        
        // Also handle Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                filterBySearch(this.value);
            }
        });
    }
});

// ===== PO STATUS FUNCTIONS =====
async function updatePOStatus(poId, newStatus) {
    if (!confirm(`Are you sure you want to ${newStatus} this PO?`)) return;
    
    try {
        const response = await fetch('../api/update_po_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({po_id: poId, status: newStatus})
        });
        
        const result = await response.json();
        if (result.success) {
            alert(`PO ${newStatus} successfully!`);
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// ===== MODAL FUNCTIONS =====
function viewPO(poId) {
    alert('View PO feature coming soon!');
}

// New item functions (simplified)
function addNewItemForm() {
    alert('Add new item feature coming soon!');
}

function saveNewItem() {
    alert('Save new item feature coming soon!');
}

function cancelNewItem() {
    document.getElementById('quickNewItemForm').style.display = 'none';
}

function addNewCategory() {
    alert('Add category feature coming soon!');
}
function openPOModal() {
    console.log('Opening PO modal...');
    const modal = document.getElementById('poModal');
    if (modal) {
        modal.classList.remove('modal-hidden');
        modal.classList.add('modal-visible');
        
        // Load data
        setTimeout(() => {
            loadSuppliersForModal();
            loadItemsForModal();
        }, 200);
    } else {
        console.error('Modal not found!');
    }
}

function closeModal(modalId) {
    console.log('Closing modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('modal-visible');
        modal.classList.add('modal-hidden');
    }
}

// ===== LOAD DATA FUNCTIONS =====
async function loadSuppliersForModal() {
    try {
        console.log('Fetching suppliers...');
        const response = await fetch('../api/get_suppliers.php');
        const suppliers = await response.json();
        console.log('Suppliers loaded:', suppliers);
        
        const select = document.getElementById('modalSupplierSelect');
        if (select) {
            select.innerHTML = '<option value="">Select Supplier</option>' +
                suppliers.map(s => `<option value="${s.id}">${s.supplier_name}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
    }
}

async function loadItemsForModal() {
    try {
        console.log('Fetching items...');
        const response = await fetch('../api/get_inventory_items.php');
        const items = await response.json();
        console.log('Items loaded:', items);
        window.inventoryItems = items;
    } catch (error) {
        console.error('Error loading items:', error);
    }
}

// ===== ITEM FUNCTIONS =====
function addItem() {
    console.log('Adding item row...');
    const tbody = document.querySelector('.items-table tbody');
    if (!tbody) {
        console.error('Table body not found!');
        return;
    }
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select class="item-select" onchange="updateItemPrice(this)" style="width: 100%; padding: 8px;">
                <option value="">Select Item</option>
                ${window.inventoryItems?.map(item => 
                    `<option value="${item.id}" data-price="${item.price}">${item.item_name} (${item.sku})</option>`
                ).join('') || '<option disabled>No items available</option>'}
            </select>
        </td>
        <td><input type="number" class="item-qty" value="1" min="1" onchange="calculateItemTotal(this)" style="width: 80px;"></td>
        <td><input type="number" class="item-price" value="0" step="0.01" onchange="calculateItemTotal(this)" style="width: 100px;"></td>
        <td class="item-total">₱0.00</td>
        <td><button type="button" onclick="removeItem(this)" style="background:none; border:none; color:#e11d48;"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(row);
}

function handleItemSelection(select) {
    const row = select.closest('tr');
    const newItemForm = row.querySelector('.new-item-form');
    
    if (select.value === '') {
        newItemForm.style.display = 'block';
    } else {
        newItemForm.style.display = 'none';
        updateItemPrice(select);
    }
}

function showNewItemForm(button) {
    const row = button.closest('tr');
    const newItemForm = row.querySelector('.new-item-form');
    newItemForm.style.display = 'block';
}

function saveInlineNewItem(button) {
    const row = button.closest('tr');
    const name = row.querySelector('.new-item-name').value;
    const sku = row.querySelector('.new-item-sku').value;
    const category = row.querySelector('.new-item-category').selectedOptions[0]?.text || 'Uncategorized';
    const reorder = row.querySelector('.new-item-reorder').value;
    
    if (!name || !sku) {
        alert('Please enter at least name and SKU');
        return;
    }
    
    // Mark this row as containing a new item
    row.setAttribute('data-is-new', 'true');
    const newItemData = {
        name: name,
        sku: sku,
        category: row.querySelector('.new-item-category').value,
        category_name: category,
        reorder: reorder
    };
    
    // Add hidden field with data
    const hiddenField = document.createElement('input');
    hiddenField.type = 'hidden';
    hiddenField.className = 'new-item-data';
    hiddenField.value = JSON.stringify(newItemData);
    row.querySelector('td:first-child').appendChild(hiddenField);
    
    // Hide the form
    row.querySelector('.new-item-form').style.display = 'none';
    
    // Update the display to show it's a new item
    const displayDiv = document.createElement('div');
    displayDiv.style.display = 'flex';
    displayDiv.style.alignItems = 'center';
    displayDiv.style.gap = '8px';
    displayDiv.innerHTML = `
        <span style="font-weight: 600; color: #2563eb;">NEW:</span>
        <span>${name} (${sku})</span>
        <small style="color: #666;">Category: ${category}</small>
    `;
    row.querySelector('td:first-child').insertBefore(displayDiv, row.querySelector('td:first-child').firstChild);
}

function updateItemPrice(select) {
    const price = select.selectedOptions[0]?.dataset.price || 0;
    const row = select.closest('tr');
    row.querySelector('.item-price').value = price;
    calculateItemTotal(row.querySelector('.item-price'));
}

function calculateItemTotal(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-total').textContent = '₱' + (qty * price).toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-total').forEach(cell => {
        subtotal += parseFloat(cell.textContent.replace('₱', '')) || 0;
    });
    
    const tax = subtotal * 0.12;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
    
    return {subtotal, tax, total};
}

function removeItem(button) {
    if (document.querySelectorAll('.items-table tbody tr').length > 1) {
        button.closest('tr').remove();
        calculateGrandTotal();
    }
}


// ===== NEW ITEM FORM FUNCTIONS (for the separate form) =====
function addNewItemForm() {
    document.getElementById('quickNewItemForm').style.display = 'block';
    document.getElementById('quickNewItemForm').scrollIntoView({ behavior: 'smooth' });
}

function cancelNewItem() {
    document.getElementById('quickNewItemForm').style.display = 'none';
    document.getElementById('new_product_name').value = '';
    document.getElementById('new_product_sku').value = '';
    document.getElementById('new_product_category').value = '';
    document.getElementById('new_product_price').value = '';
    document.getElementById('new_product_stock').value = '0';
    document.getElementById('new_product_reorder').value = '10';
    document.getElementById('new_product_description').value = '';
    document.getElementById('newCategoryInput').style.display = 'none';
}

function addNewCategory() {
    const categoryName = document.getElementById('new_category_name').value.trim();
    if (!categoryName) {
        alert('Please enter a category name');
        return;
    }
    
    const select = document.getElementById('new_product_category');
    const newOption = document.createElement('option');
    newOption.value = 'temp_' + Date.now();
    newOption.text = categoryName;
    newOption.selected = true;
    select.appendChild(newOption);
    
    document.getElementById('newCategoryInput').style.display = 'none';
    document.getElementById('new_category_name').value = '';
    
    alert('Category added! You can now select it.');
}

function saveNewItem() {
    const name = document.getElementById('new_product_name').value.trim();
    const sku = document.getElementById('new_product_sku').value.trim();
    const price = document.getElementById('new_product_price').value;
    
    if (!name || !sku || !price || price <= 0) {
        alert('Please fill in all required fields (Name, SKU, Price)');
        return;
    }
    
    const tbody = document.querySelector('.items-table tbody');
    const row = document.createElement('tr');
    row.setAttribute('data-is-new', 'true');
    
    const category = document.getElementById('new_product_category').selectedOptions[0]?.text || 'Uncategorized';
    const reorderLevel = document.getElementById('new_product_reorder').value;
    
    const newItemData = {
        name: name,
        sku: sku,
        category: document.getElementById('new_product_category').value,
        category_name: category,
        reorder: reorderLevel,
        description: document.getElementById('new_product_description').value
    };
    
    row.innerHTML = `
        <td>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 600; color: #2563eb;">NEW:</span>
                <span>${name} (${sku})</span>
            </div>
            <small style="color: #666;">Category: ${category} | Reorder: ${reorderLevel}</small>
            <input type="hidden" class="new-item-data" value='${JSON.stringify(newItemData)}'>
        </td>
        <td><input type="number" class="item-qty" value="1" min="1" onchange="calculateItemTotal(this)" style="width: 80px;"></td>
        <td><input type="number" class="item-price" value="${price}" step="0.01" onchange="calculateItemTotal(this)" style="width: 100px;"></td>
        <td class="item-total">₱${(parseFloat(price) * 1).toFixed(2)}</td>
        <td><button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button></td>
    `;
    
    tbody.appendChild(row);
    cancelNewItem();
    calculateGrandTotal();
}

// ===== SAVE PO FUNCTION =====
async function savePO() {
    console.log('Saving PO...');
    
    const supplier = document.getElementById('modalSupplierSelect').value;
    const orderDate = document.getElementById('orderDate').value;
    const priority = document.getElementById('priority').value;
    const deliveryDate = document.getElementById('expectedDelivery').value;
    const description = document.getElementById('poDescription').value;
    const additionalNotes = document.getElementById('additionalNotes').value;
    
    if (!supplier || !orderDate) {
        alert('Please select a supplier and order date');
        return;
    }
    
    const items = [];
    const itemRows = document.querySelectorAll('.items-table tbody tr');
    
    if (itemRows.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    itemRows.forEach(row => {
        if (row.getAttribute('data-is-new') === 'true') {
            const newItemData = JSON.parse(row.querySelector('.new-item-data').value);
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            
            items.push({
                is_new: true,
                new_item_name: newItemData.name,
                new_sku: newItemData.sku,
                new_category: newItemData.category,
                new_category_name: newItemData.category_name,
                new_reorder_level: newItemData.reorder,
                new_description: newItemData.description || '',
                quantity: qty,
                unit_price: price,
                total_price: qty * price
            });
        } else {
            const itemSelect = row.querySelector('.item-select');
            if (itemSelect && itemSelect.value) {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                
                items.push({
                    item_id: itemSelect.value,
                    quantity: qty,
                    unit_price: price,
                    total_price: qty * price
                });
            }
        }
    });
    
    if (items.length === 0) {
        alert('Please select at least one item');
        return;
    }
    
    const totals = calculateGrandTotal();
    const combinedNotes = [description, additionalNotes].filter(n => n).join('\n');
    
    const poData = {
        supplier_id: supplier,
        order_date: orderDate,
        expected_delivery: deliveryDate || null,
        priority: priority,
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
            alert(result.message || `PO ${result.po_number} created successfully!`);
            closeModal('poModal');
            document.getElementById('poForm').reset();
            const tbody = document.querySelector('.items-table tbody');
            tbody.innerHTML = '';
            addItem();
            window.location.reload();
        } else {
            alert('Error creating PO: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving PO:', error);
        alert('Error saving purchase order: ' + error.message);
    }
}

// ===== PO FUNCTIONS =====

// View PO Details
async function viewPO(poId) {
    console.log('Viewing PO:', poId);
    
    try {
        const response = await fetch(`../api/get_po_details.php?po_id=${poId}`);
        const data = await response.json();
        
        if (data.success) {
            showPODetailsModal(data.po);
        } else {
            alert('Error loading PO details: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load PO details');
    }
}

// Show PO Details Modal
function showPODetailsModal(po) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('poDetailsModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'poDetailsModal';
        modal.className = 'modal modal-hidden';
        document.body.appendChild(modal);
    }
    
    // Format items table
    let itemsHtml = '';
    po.items.forEach(item => {
        itemsHtml += `
            <tr>
                <td>${item.item_name}</td>
                <td>${item.quantity}</td>
                <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                <td>₱${parseFloat(item.total_price).toFixed(2)}</td>
            </tr>
        `;
    });
    
    // Set modal content
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-file-invoice"></i> 
                    PO Details: ${po.po_number}
                </h3>
                <button class="modal-close" onclick="closeModal('poDetailsModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div style="padding: 24px; max-height: 70vh; overflow-y: auto;">
                <!-- Status Banner -->
                <div style="background: ${getStatusColor(po.status)}; color: white; padding: 12px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">Status: ${po.status.toUpperCase()}</span>
                    <span>Priority: ${po.priority.toUpperCase()}</span>
                </div>
                
                <!-- Basic Info -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4 style="color: #666; margin-bottom: 10px;">Supplier Information</h4>
                        <p><strong>Name:</strong> ${po.supplier_name}</p>
                        ${po.supplier_contact ? `<p><strong>Contact:</strong> ${po.supplier_contact}</p>` : ''}
                        ${po.supplier_email ? `<p><strong>Email:</strong> ${po.supplier_email}</p>` : ''}
                    </div>
                    <div>
                        <h4 style="color: #666; margin-bottom: 10px;">Order Information</h4>
                        <p><strong>Order Date:</strong> ${formatDate(po.order_date)}</p>
                        <p><strong>Expected Delivery:</strong> ${po.expected_delivery ? formatDate(po.expected_delivery) : 'Not set'}</p>
                        <p><strong>Created By:</strong> ${po.requester || 'Unknown'}</p>
                    </div>
                </div>
                
                <!-- Items Table -->
                <h4 style="color: #666; margin: 20px 0 10px;">Items</h4>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 12px; text-align: left;">Item</th>
                            <th style="padding: 12px; text-align: right;">Qty</th>
                            <th style="padding: 12px; text-align: right;">Unit Price</th>
                            <th style="padding: 12px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot style="border-top: 2px solid #e2e8f0;">
                        <tr>
                            <td colspan="3" style="padding: 12px; text-align: right;"><strong>Subtotal:</strong></td>
                            <td style="padding: 12px; text-align: right;">₱${parseFloat(po.subtotal).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding: 12px; text-align: right;"><strong>Tax (12%):</strong></td>
                            <td style="padding: 12px; text-align: right;">₱${parseFloat(po.tax_amount).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding: 12px; text-align: right;"><strong>Total:</strong></td>
                            <td style="padding: 12px; text-align: right; font-size: 18px; font-weight: 700; color: #2563eb;">
                                ₱${parseFloat(po.total_amount).toFixed(2)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
                <!-- Notes -->
                ${po.notes ? `
                <div style="background: #f8fafc; padding: 16px; border-radius: 8px;">
                    <h4 style="color: #666; margin-bottom: 8px;">Notes</h4>
                    <p style="margin: 0; white-space: pre-line;">${po.notes}</p>
                </div>
                ` : ''}
            </div>
            
            <div class="modal-footer" style="justify-content: space-between;">
                <div>
                    ${po.status === 'pending' ? `
                        <button class="btn btn-outline" onclick="editPO(${po.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    ` : ''}
                </div>
                <div>
                    <button class="btn btn-outline" onclick="closeModal('poDetailsModal')">Close</button>
                    ${po.status === 'pending' && userRole === 'admin' ? `
                        <button class="btn btn-primary" onclick="updatePOStatus(${po.id}, 'approved')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-danger" onclick="updatePOStatus(${po.id}, 'rejected')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    ` : ''}
                    ${po.status === 'approved' && userRole === 'admin' ? `
                        <button class="btn btn-primary" onclick="updatePOStatus(${po.id}, 'completed')">
                            <i class="fas fa-check-double"></i> Complete
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    // Show modal
    modal.classList.remove('modal-hidden');
    modal.classList.add('modal-visible');
}

// Edit PO
async function editPO(poId) {
    console.log('Editing PO:', poId);
    
    try {
        const response = await fetch(`../api/get_po_details.php?po_id=${poId}`);
        const data = await response.json();
        
        if (data.success) {
            openEditPOModal(data.po);
        } else {
            alert('Error loading PO details: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load PO details for editing');
    }
}

// Open Edit PO Modal
function openEditPOModal(po) {
    // First open the main PO modal
    openPOModal();
    
    // Wait for modal to be ready
    setTimeout(() => {
        // Fill in basic info
        document.getElementById('poNumber').value = po.po_number;
        document.getElementById('orderDate').value = po.order_date;
        document.getElementById('expectedDelivery').value = po.expected_delivery || '';
        document.getElementById('priority').value = po.priority || 'normal';
        document.getElementById('poDescription').value = po.notes || '';
        document.getElementById('additionalNotes').value = '';
        
        // Select supplier
        const supplierSelect = document.getElementById('modalSupplierSelect');
        if (supplierSelect) {
            // Wait for suppliers to load then set value
            const checkSupplier = setInterval(() => {
                if (supplierSelect.options.length > 1) {
                    supplierSelect.value = po.supplier_id;
                    
                    // Trigger change to load items for this supplier
                    filterItemsBySupplier();
                    
                    // After items load, populate the items
                    setTimeout(() => {
                        populatePOItems(po.items);
                    }, 500);
                    
                    clearInterval(checkSupplier);
                }
            }, 100);
        }
        
        // Change modal title
        const modalTitle = document.querySelector('#poModal .modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Purchase Order';
        }
        
        // Change save button
        const saveBtn = document.querySelector('#poModal .btn-primary');
        if (saveBtn) {
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Update Purchase Order';
            saveBtn.setAttribute('onclick', `updatePO(${po.id})`);
        }
    }, 500);
}

// Populate items in edit modal
function populatePOItems(items) {
    const tbody = document.querySelector('.items-table tbody');
    if (!tbody) return;
    
    tbody.innerHTML = ''; // Clear existing rows
    
    items.forEach(item => {
        const row = document.createElement('tr');
        
        // Create dropdown with this item selected
        let options = `<option value="${item.item_id}" data-price="${item.unit_price}" selected>
            ${item.item_name} - ₱${parseFloat(item.unit_price).toFixed(2)}
        </option>`;
        
        // Add other available items from filteredItems (optional)
        if (window.filteredItems && window.filteredItems.length > 0) {
            window.filteredItems.forEach(filteredItem => {
                if (filteredItem.id != item.item_id) {
                    options += `<option value="${filteredItem.id}" data-price="${filteredItem.price}">
                        ${filteredItem.item_name} (${filteredItem.sku}) - ₱${parseFloat(filteredItem.price).toFixed(2)}
                    </option>`;
                }
            });
        }
        
        row.innerHTML = `
            <td>
                <select class="item-select" onchange="updateItemPrice(this)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    ${options}
                </select>
            </td>
            <td><input type="number" class="item-qty" value="${item.quantity}" min="1" onchange="calculateItemTotal(this)" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></td>
            <td><input type="number" class="item-price" value="${item.unit_price}" step="0.01" onchange="calculateItemTotal(this)" style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></td>
            <td class="item-total" style="font-weight: 600;">₱${parseFloat(item.total_price || (item.quantity * item.unit_price)).toFixed(2)}</td>
            <td><button type="button" onclick="removeItem(this)" style="background:none; border:none; color:#e11d48; cursor:pointer;"><i class="fas fa-times"></i></button></td>
        `;
        tbody.appendChild(row);
    });
    
    calculateGrandTotal();
}

// Update PO
async function updatePO(poId) {
    console.log('Updating PO:', poId);
    
    // Get form values
    const supplier = document.getElementById('modalSupplierSelect').value;
    const orderDate = document.getElementById('orderDate').value;
    const priority = document.getElementById('priority').value;
    const deliveryDate = document.getElementById('expectedDelivery').value;
    const description = document.getElementById('poDescription').value;
    const additionalNotes = document.getElementById('additionalNotes').value;
    
    if (!supplier || !orderDate) {
        alert('Please select a supplier and order date');
        return;
    }
    
    // Get items
    const items = [];
    const itemRows = document.querySelectorAll('.items-table tbody tr');
    
    itemRows.forEach(row => {
        const itemSelect = row.querySelector('.item-select');
        const qtyInput = row.querySelector('.item-qty');
        const priceInput = row.querySelector('.item-price');
        
        if (itemSelect && itemSelect.value) {
            items.push({
                item_id: parseInt(itemSelect.value),
                quantity: parseFloat(qtyInput?.value) || 0,
                unit_price: parseFloat(priceInput?.value) || 0
                // total_price will be calculated on server
            });
        }
    });
    
    if (items.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    const totals = calculateGrandTotal();
    const combinedNotes = [description, additionalNotes].filter(n => n).join('\n');
    
    const poData = {
        po_id: poId,
        supplier_id: parseInt(supplier),
        order_date: orderDate,
        expected_delivery: deliveryDate || null,
        priority: priority,
        subtotal: totals.subtotal,
        tax_amount: totals.tax,
        total_amount: totals.total,
        notes: combinedNotes,
        items: items
    };
    
    console.log('Sending update data:', poData);
    
    try {
        const response = await fetch('../api/update_po.php', {
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
        console.log('Update response:', result);
        
        if (result.success) {
            alert('PO updated successfully!');
            closeModal('poModal');
            window.location.reload();
        } else {
            alert('Error updating PO: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating purchase order: ' + error.message);
    }
}

// Update PO Status (uses your existing update_po_status.php)
async function updatePOStatus(poId, newStatus) {
    const action = newStatus === 'approved' ? 'approve' : 
                   newStatus === 'rejected' ? 'reject' : 
                   newStatus === 'completed' ? 'complete' : newStatus;
    
    if (newStatus === 'rejected') {
        const reason = prompt('Please provide a reason for rejection:');
        if (reason === null) return;
        
        if (!confirm(`Are you sure you want to reject this PO?`)) return;
        
        try {
            const response = await fetch('../api/update_po_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    po_id: poId,
                    status: newStatus,
                    rejection_reason: reason
                })
            });
            
            const result = await response.json();
            if (result.success) {
                alert(`PO ${newStatus} successfully!`);
                window.location.reload();
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating PO status');
        }
    } else {
        if (!confirm(`Are you sure you want to ${action} this PO?`)) return;
        
        try {
            const response = await fetch('../api/update_po_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    po_id: poId,
                    status: newStatus
                })
            });
            
            const result = await response.json();
            console.log('Status update response:', result);
            
            if (result.success) {
                alert(`PO ${newStatus} successfully!`);
                window.location.reload();
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error updating PO status');
        }
    }
}

// Helper functions
function getStatusColor(status) {
    const colors = {
        'draft': '#6b7280',
        'pending': '#f59e0b',
        'approved': '#10b981',
        'rejected': '#ef4444',
        'completed': '#2563eb',
        'cancelled': '#6b7280'
    };
    return colors[status] || '#6b7280';
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

// Make sure to also update your filterItemsBySupplier function if it's not already defined
if (typeof window.filterItemsBySupplier !== 'function') {
    window.filterItemsBySupplier = function() {
        const supplierId = document.getElementById('modalSupplierSelect').value;
        console.log('Filtering items for supplier:', supplierId);
        // Your existing filterItemsBySupplier logic here
    };
}

// Make functions globally available
window.viewPO = viewPO;
window.editPO = editPO;
window.updatePOStatus = updatePOStatus;



// Update PO




