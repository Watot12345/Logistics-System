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

function viewPO(poId) {
    console.log('Viewing PO:', poId);
    alert('View PO feature coming soon!');
}