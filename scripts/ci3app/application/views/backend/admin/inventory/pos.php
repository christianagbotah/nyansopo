<!-- Enterprise Point of Sale -->
<?php include('_readable_header.php'); ?>
<link href="<?php echo base_url(); ?>assets/cdn/css/select2-4.1.0.min.css" rel="stylesheet" />
<script src="<?php echo base_url(); ?>assets/cdn/js/select2-4.1.0.min.js"></script>

<div class="inventory-content p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
    <div class="mb-8">
        <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900">Point of Sale</h1>
        <p style="font-size: 1.5rem !important;" class="mt-2 text-gray-600">Process sales and manage transactions</p>
    </div>

    <!-- Daily Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div style="font-size: 1.125rem !important;" class="opacity-90 mb-2">Today's Sales</div>
            <div style="font-size: 2.5rem !important;" class="font-bold" id="stat_sales"><?php echo $currency; ?> 0.00</div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div style="font-size: 1.125rem !important;" class="opacity-90 mb-2">Transactions</div>
            <div style="font-size: 2.5rem !important;" class="font-bold" id="stat_count">0</div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div style="font-size: 1.125rem !important;" class="opacity-90 mb-2">Customers</div>
            <div style="font-size: 2.5rem !important;" class="font-bold" id="stat_customers">0</div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div style="font-size: 1.125rem !important;" class="opacity-90 mb-2">Avg. Sale</div>
            <div style="font-size: 2.5rem !important;" class="font-bold" id="stat_avg"><?php echo $currency; ?> 0.00</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Product Selection -->
        <div class="lg:col-span-2" style="max-height: calc(100vh - 200px); overflow-y: auto;">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="font-semibold text-gray-800" style="font-size: 1.5rem !important;">Product Search</h3>
                </div>
                <div class="p-6">
                    <input type="text" id="product_search" placeholder="Search products..." style="font-size: 1.5rem !important; min-height: 4rem !important;" class="block w-full px-5 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>
                <div class="px-6 pb-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5" id="products_grid">
                        <div style="font-size: 1.5rem !important;" class="col-span-full text-center py-8 text-gray-500">Search for products...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart & Checkout -->
        <div class="lg:col-span-1" style="max-height: calc(100vh - 200px); overflow-y: auto;">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <!-- Customer Selection -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50">
                    <h3 class="font-semibold text-gray-800" style="font-size: 1.5rem !important;">Customer Selection</h3>
                </div>
                <div class="px-6 py-4 border-b border-gray-200">
                    <label style="font-size: 1.25rem !important;" class="block font-medium text-gray-700 mb-2">Selection Type</label>
                    
                    <!-- Selection Type -->
                    <div class="mb-4">
                        <select id="selection_type" onchange="toggleSelectionMode()" style="font-size: 1.375rem !important; min-height: 3.5rem !important;" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="individual">Individual Students</option>
                            <option value="class">By Class</option>
                            <option value="residential">By Residential Type</option>
                        </select>
                    </div>

                    <!-- Individual Students (Multi-Select) -->
                    <div id="individual_select" class="mb-3">
                        <select id="customer_select" multiple="multiple" style="width: 100%; font-size: 1.375rem !important;">
                            <option value="walk-in">Walk-in Customer</option>
                            <?php 
                            $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
                            $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
                            $this->db->select('s.student_id, s.name, c.name as class_name, c.name_numeric, sec.name as section_name');
                            $this->db->from('student s');
                            $this->db->join('enroll e', 'e.student_id = s.student_id');
                            $this->db->join('class c', 'c.class_id = e.class_id', 'left');
                            $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
                            $this->db->where('e.year', $year);
                            $this->db->where('e.term', $term);
                            $this->db->where('e.mute', '0');
                            $this->db->order_by('c.name, c.name_numeric, sec.name, s.name');
                            foreach($this->db->get()->result() as $student): 
                                $class_full = trim($student->class_name . ' ' . $student->name_numeric . ' ' . $student->section_name);
                            ?>
                            <option value="<?php echo $student->student_id; ?>"><?php echo $student->name; ?> - <?php echo $class_full; ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Walk-in Customer Name Input (Hidden by default) -->
                        <div id="walkin_name_container" class="mt-3 hidden">
                            <label style="font-size: 1.25rem !important;" class="block font-medium text-gray-700 mb-2">
                                <i class="fa fa-user mr-2 text-blue-600"></i>Walk-in Customer Name (Optional)
                            </label>
                            <input type="text" id="walkin_customer_name" placeholder="Enter customer name (leave blank for 'Walk-in Customer')" style="font-size: 1.375rem !important; min-height: 3.5rem !important;" class="block w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p style="font-size: 1rem !important;" class="text-gray-500 mt-2 italic">
                                <i class="fa fa-info-circle mr-1"></i>If left blank, will be saved as "Walk-in Customer"
                            </p>
                        </div>
                    </div>

                    <!-- By Class -->
                    <div id="class_select" class="mb-3 hidden">
                        <select id="class_filter" multiple="multiple" style="width: 100%; font-size: 1.375rem !important;">
                            <?php 
                            $year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
                            $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
                            $class_order = ['CRECHE', 'NURSERY', 'KG', 'BASIC', 'JHS'];
                            $classes_data = [];
                            foreach($class_order as $class_name) {
                                $this->db->select('c.class_id, c.name, c.name_numeric, sec.name as section_name, sec.section_id');
                                $this->db->from('class c');
                                $this->db->join('enroll e', 'e.class_id = c.class_id');
                                $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
                                $this->db->where('c.name', $class_name);
                                $this->db->where('e.year', $year);
                                $this->db->where('e.term', $term);
                                $this->db->where('e.mute', '0');
                                $this->db->group_by('c.class_id, sec.section_id');
                                $this->db->order_by('c.name_numeric', 'asc');
                                $this->db->order_by('sec.name', 'asc');
                                $result = $this->db->get()->result();
                                foreach($result as $r) { $classes_data[] = $r; }
                            }
                            foreach($classes_data as $class): 
                                $class_full = trim($class->name . ' ' . $class->name_numeric . ' ' . $class->section_name);
                            ?>
                            <option value="<?php echo $class->class_id; ?>-<?php echo $class->section_id; ?>"><?php echo $class_full; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- By Residential Type -->
                    <div id="residential_select" class="mb-3 hidden">
                        <select id="residential_filter" multiple="multiple" style="width: 100%; font-size: 1.375rem !important;">
                            <option value="day">Day Students</option>
                            <option value="boarding">Boarding Students</option>
                        </select>
                    </div>

                    <div style="font-size: 1.25rem !important;" class="text-gray-600 mt-2">
                        <span id="customer_count">0</span> customer(s) selected
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
                    <h3 class="font-semibold text-gray-800" style="font-size: 1.5rem !important;">Cart Items</h3>
                </div>
                <div class="px-6 py-4 border-b border-gray-200">
                    <div id="cart_items" class="space-y-3 max-h-80 overflow-y-auto">
                        <div style="font-size: 1.5rem !important;" class="text-center py-10 text-gray-500">Cart is empty</div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-amber-50 to-orange-50">
                    <h3 class="font-semibold text-gray-800 mb-3" style="font-size: 1.5rem !important;">Order Summary</h3>
                    <div class="space-y-3">
                    <div class="flex justify-between">
                        <span style="font-size: 1.375rem !important;" class="text-gray-600 font-medium">Subtotal</span>
                        <span style="font-size: 1.375rem !important;" class="font-semibold text-gray-900" id="subtotal"><?php echo $currency; ?> 0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="font-size: 1.375rem !important;" class="text-gray-600 font-medium">Customers</span>
                        <span style="font-size: 1.375rem !important;" class="font-semibold text-gray-900" id="customer_multiplier">× 1</span>
                    </div>
                    <div class="flex justify-between border-t pt-3">
                        <span style="font-size: 1.75rem !important;" class="text-gray-900 font-bold">Total</span>
                        <span style="font-size: 1.75rem !important;" class="text-gray-900 font-bold" id="total"><?php echo $currency; ?> 0.00</span>
                    </div>
                    </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 space-y-3 bg-gray-50">
                    <button onclick="processSale()" id="checkout_btn" disabled style="font-size: 1.5rem !important; min-height: 4rem !important;" class="w-full px-6 py-4 border border-transparent font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Complete Sale
                    </button>
                    <button onclick="clearCart()" style="font-size: 1.375rem !important; min-height: 3.5rem !important;" class="w-full px-6 py-3 border border-gray-300 font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Clear Cart
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="lg:col-span-3 mt-8">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800" style="font-size: 1.5rem !important;">Recent Transactions</h3>
                    <button onclick="loadRecentTransactions()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" style="font-size: 1.125rem !important;">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
                <div class="p-6">
                    <div id="recent_transactions" class="overflow-x-auto">
                        <div style="font-size: 1.5rem !important;" class="text-center py-8 text-gray-500">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo form_open('inventory/process_sale', ['id' => 'pos_form', 'class' => 'hidden']); ?>
<?php echo form_close(); ?>

<style>
.select2-container--default .select2-selection--multiple {
    min-height: 3.5rem !important;
    padding: 0.625rem !important;
    font-size: 1.375rem !important;
    border: 1px solid #d1d5db !important;
    border-radius: 0.5rem !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    font-size: 1.25rem !important;
    padding: 0.5rem 0.75rem !important;
    background-color: #3b82f6 !important;
    border: none !important;
    color: white !important;
    border-radius: 0.375rem !important;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: white !important;
    margin-right: 0.5rem !important;
    font-size: 1.25rem !important;
}
.select2-results__option {
    font-size: 1.375rem !important;
    padding: 0.75rem 1rem !important;
}
</style>

<script>
let cart = [];
let selectedCustomers = [];
let currency = '<?php echo $currency; ?>';

$(document).ready(function() {
    loadDailyStats();
    loadRecentTransactions();
    setInterval(loadDailyStats, 30000); // Refresh every 30 seconds
    
    $('#product_search').on('keyup', debounce(searchProducts, 300));
    
    // Initialize Select2
    $('#customer_select').select2({
        placeholder: 'Search and select customers...',
        allowClear: true,
        width: '100%'
    }).on('change', function() {
        updateSelectedCustomers();
        toggleWalkinNameInput();
    });

    $('#class_filter').select2({
        placeholder: 'Select classes...',
        allowClear: true,
        width: '100%'
    }).on('change', function() {
        loadStudentsByClass();
    });

    $('#residential_filter').select2({
        placeholder: 'Select residential type...',
        allowClear: true,
        width: '100%'
    }).on('change', function() {
        loadStudentsByResidential();
    });
});

function toggleSelectionMode() {
    const mode = $('#selection_type').val();
    $('#individual_select, #class_select, #residential_select').addClass('hidden');
    $('#walkin_name_container').addClass('hidden'); // Hide walk-in input when switching modes
    
    if(mode === 'individual') {
        $('#individual_select').removeClass('hidden');
        updateSelectedCustomers();
        toggleWalkinNameInput(); // Check if walk-in is selected
    } else if(mode === 'class') {
        $('#class_select').removeClass('hidden');
        loadStudentsByClass();
    } else if(mode === 'residential') {
        $('#residential_select').removeClass('hidden');
        loadStudentsByResidential();
    }
}

function toggleWalkinNameInput() {
    const selectedValues = $('#customer_select').val() || [];
    // Show input if 'walk-in' is in the selected values and it's the only selection
    if(selectedValues.includes('walk-in') && selectedValues.length === 1) {
        $('#walkin_name_container').removeClass('hidden');
    } else {
        $('#walkin_name_container').addClass('hidden');
        $('#walkin_customer_name').val(''); // Clear the input when hidden
    }
}

function updateSelectedCustomers() {
    selectedCustomers = $('#customer_select').val() || [];
    updateCustomerCount();
}

function loadStudentsByClass() {
    const classes = $('#class_filter').val() || [];
    if(classes.length === 0) {
        selectedCustomers = [];
        updateCustomerCount();
        return;
    }
    
    $.post('<?php echo site_url('inventory/get_students_by_class'); ?>', {classes: classes}, function(response) {
        const data = JSON.parse(response);
        selectedCustomers = data.student_ids || [];
        updateCustomerCount();
    });
}

function loadStudentsByResidential() {
    const types = $('#residential_filter').val() || [];
    if(types.length === 0) {
        selectedCustomers = [];
        updateCustomerCount();
        return;
    }
    
    $.post('<?php echo site_url('inventory/get_students_by_residential'); ?>', {types: types}, function(response) {
        const data = JSON.parse(response);
        selectedCustomers = data.student_ids || [];
        updateCustomerCount();
    });
}

function updateCustomerCount() {
    const count = selectedCustomers.length || 1;
    $('#customer_count').text(count);
    $('#customer_multiplier').text('× ' + count);
    updateTotals();
}

function searchProducts() {
    const query = $('#product_search').val();
    if(query.length < 2) {
        $('#products_grid').html('<div style="font-size: 1.5rem !important;" class="col-span-full text-center py-8 text-gray-500">Enter at least 2 characters...</div>');
        return;
    }
    
    $.get('<?php echo site_url('inventory/search_products'); ?>', {q: query}, function(response) {
        renderProducts(JSON.parse(response));
    });
}

function renderProducts(products) {
    let html = '';
    if(products.length === 0) {
        html = '<div style="font-size: 1.5rem !important;" class="col-span-full text-center py-8 text-gray-500">No products found</div>';
    } else {
        products.forEach(product => {
            if(product.quantity > 0 && product.status == 1) {
                html += `
                    <button onclick="addToCart(${product.id}, '${product.name}', ${product.selling_price}, ${product.quantity})" class="p-6 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition-all text-left">
                        <div style="font-size: 1.375rem !important;" class="font-semibold text-gray-900 mb-2">${product.name}</div>
                        <div style="font-size: 1.125rem !important;" class="text-gray-600 mb-3">Stock: ${product.quantity}</div>
                        <div style="font-size: 1.5rem !important;" class="font-bold text-blue-600"><sup style="font-size: 0.7em;">${currency}</sup> ${parseFloat(product.selling_price).toFixed(2)}</div>
                    </button>
                `;
            }
        });
    }
    $('#products_grid').html(html);
}

function addToCart(id, name, price, maxQty) {
    const existing = cart.find(item => item.id === id);
    if(existing) {
        if(existing.quantity < maxQty) {
            existing.quantity++;
        } else {
            showAjaxModal_alert('Maximum stock reached', 'warning');
            return;
        }
    } else {
        cart.push({id, name, price, quantity: 1, maxQty});
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

function updateQuantity(id, quantity) {
    const item = cart.find(i => i.id === id);
    if(item && quantity > 0 && quantity <= item.maxQty) {
        item.quantity = quantity;
        renderCart();
    }
}

function renderCart() {
    let html = '';
    let subtotal = 0;
    
    if(cart.length === 0) {
        html = '<div style="font-size: 1.5rem !important;" class="text-center py-8 text-gray-500">Cart is empty</div>';
        $('#checkout_btn').prop('disabled', true);
    } else {
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            html += `
                <div class="flex items-center justify-between py-3 border-b border-gray-100">
                    <div class="flex-1 min-w-0 mr-3">
                        <p style="font-size: 1.375rem !important;" class="font-semibold text-gray-900 truncate">${item.name}</p>
                        <p style="font-size: 1.125rem !important;" class="text-gray-600 mt-1"><sup style="font-size: 0.7em;">${currency}</sup> ${item.price.toFixed(2)} each</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <input type="number" value="${item.quantity}" min="1" max="${item.maxQty}" onchange="updateQuantity(${item.id}, this.value)" style="font-size: 1.375rem !important;" class="w-24 px-3 py-2 border border-gray-300 rounded text-center focus:ring-blue-500 focus:border-blue-500">
                        <button onclick="removeFromCart(${item.id})" class="text-red-600 hover:text-red-800">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        });
        $('#checkout_btn').prop('disabled', false);
    }
    
    $('#cart_items').html(html);
    $('#subtotal').html('<sup style="font-size: 0.7em;">' + currency + '</sup> ' + subtotal.toFixed(2));
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const customerCount = selectedCustomers.length || 1;
    const total = subtotal * customerCount;
    $('#total').html('<sup style="font-size: 0.7em;">' + currency + '</sup> ' + total.toFixed(2));
}

function clearCart() {
    if(cart.length === 0) return;
    showConfirmModal('Clear Cart', 'Remove all items from cart?', function() {
        cart = [];
        renderCart();
    }, 'Clear', 'warning');
}

function processSale() {
    if(cart.length === 0) return;
    if(selectedCustomers.length === 0) {
        showAjaxModal_alert('Please select at least one customer', 'warning');
        return;
    }
    
    showAjaxModal_alert('Processing sale...', 'loading');
    
    const formData = new FormData();
    formData.append('customer_ids', JSON.stringify(selectedCustomers));
    formData.append('items', JSON.stringify(cart));
    formData.append('selection_type', $('#selection_type').val());
    
    // Add walk-in customer name if applicable
    if(selectedCustomers.includes('walk-in') || selectedCustomers.includes('')) {
        const walkinName = $('#walkin_customer_name').val().trim();
        if(walkinName) {
            formData.append('walkin_customer_name', walkinName);
        }
    }
    
    formData.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');
    
    $.ajax({
        url: '<?php echo site_url('inventory/process_sale'); ?>',
        type: 'POST',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            // Show success message with view details button
            const detailsButton = response.sale_id ? 
                `<button onclick="viewSaleDetails(${response.sale_id})" class="mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" style="font-size: 1.25rem !important;">
                    <i class="fa fa-receipt"></i> View Details & Print
                </button>` : '';
            
            showAjaxModal_alert(response.message + '<br><br>' + detailsButton, 'success', false);
            
            cart = [];
            selectedCustomers = [];
            renderCart();
            $('#customer_select, #class_filter, #residential_filter').val(null).trigger('change');
            $('#walkin_customer_name').val(''); // Clear walk-in name
            $('#walkin_name_container').addClass('hidden'); // Hide walk-in input
            $('#product_search').val('');
            $('#products_grid').html('<div style="font-size: 1.5rem !important;" class="col-span-full text-center py-8 text-gray-500">Search for products...</div>');
            updateCustomerCount();
            loadDailyStats();
            loadRecentTransactions();
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function debounce(func, wait) {
    let timeout;
    return function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, arguments), wait);
    };
}

function loadDailyStats() {
    $.get('<?php echo site_url('inventory/get_daily_stats'); ?>', function(response) {
        const data = JSON.parse(response);
        currency = data.currency || currency;
        $('#stat_sales').html('<sup style="font-size: 0.6em;">' + currency + '</sup> ' + parseFloat(data.total_sales).toFixed(2));
        $('#stat_count').text(data.transaction_count);
        $('#stat_customers').text(data.customer_count);
        $('#stat_avg').html('<sup style="font-size: 0.6em;">' + currency + '</sup> ' + parseFloat(data.avg_sale).toFixed(2));
    });
}

function loadRecentTransactions() {
    $.get('<?php echo site_url('inventory/get_recent_transactions'); ?>', function(response) {
        const data = JSON.parse(response);
        let html = '';
        
        if(data.length === 0) {
            html = '<div style="font-size: 1.5rem !important;" class="text-center py-8 text-gray-500">No transactions today</div>';
            $('#recent_transactions').html(html);
        } else {
            html = '<table id="pos_transactions_table" class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
            html += '<th style="font-size: 1.25rem !important;" class="px-6 py-3 text-left font-medium text-gray-700">Time</th>';
            html += '<th style="font-size: 1.25rem !important;" class="px-6 py-3 text-left font-medium text-gray-700">Customer</th>';
            html += '<th style="font-size: 1.25rem !important;" class="px-6 py-3 text-left font-medium text-gray-700">Items</th>';
            html += '<th style="font-size: 1.25rem !important;" class="px-6 py-3 text-right font-medium text-gray-700">Amount</th>';
            html += '<th style="font-size: 1.25rem !important;" class="px-6 py-3 text-center font-medium text-gray-700">Action</th>';
            html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
            
            data.forEach(sale => {
                html += '<tr class="hover:bg-gray-50">';
                html += '<td style="font-size: 1.25rem !important;" class="px-6 py-4 whitespace-nowrap">' + sale.time + '</td>';
                html += '<td style="font-size: 1.25rem !important;" class="px-6 py-4">' + sale.customer_name + '</td>';
                html += '<td style="font-size: 1.25rem !important;" class="px-6 py-4">' + sale.item_count + ' item(s)</td>';
                html += '<td style="font-size: 1.25rem !important;" class="px-6 py-4 text-right font-semibold"><sup style="font-size: 0.6em; vertical-align: super;">' + currency + '</sup> ' + parseFloat(sale.total_amount).toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td style="font-size: 1.25rem !important;" class="px-6 py-4 text-center">';
                html += '<button onclick="viewSaleDetails(' + sale.id + ')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors" title="View Details & Print">';
                html += '<i class="fa fa-receipt"></i></button></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $('#recent_transactions').html(html);
            
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#pos_transactions_table')) {
                $('#pos_transactions_table').DataTable().destroy();
            }
            
            // Initialize DataTable
            $('#pos_transactions_table').DataTable({
                "pageLength": 10,
                "ordering": true,
                "searching": true,
                "lengthChange": true,
                "info": true,
                "autoWidth": false,
                "order": [[0, 'desc']],
                "language": {
                    "search": "Search transactions:",
                    "lengthMenu": "Show _MENU_ transactions",
                    "info": "Showing _START_ to _END_ of _TOTAL_ transactions",
                    "infoEmpty": "Showing 0 to 0 of 0 transactions",
                    "infoFiltered": "(filtered from _MAX_ total)",
                    "zeroRecords": "No transactions found",
                    "emptyTable": "No transactions today"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [4] }
                ]
            });
        }
    });
}

// Use the exact same function as sales history page
function viewSaleDetails(id) {
    loadModalContent('detailsModal', '<?php echo site_url('inventory/sale_details/'); ?>' + id, '<i class="fa fa-receipt"></i> Sale Details');
}

function printReceiptNow(saleId, currency, school, slogan) {
    if(!window.currentSale || !window.currentItems) { 
        showAjaxModal_alert('No sale data loaded. Please select a sale to print.', 'Warning', false, true); 
        return; 
    }
    
    const sale = window.currentSale;
    const items = window.currentItems;
    const customer = sale.customer_name || 'Walk-in Customer';
    const date = new Date(sale.sale_date);
    const dateFormatted = date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', {hour:'2-digit',minute:'2-digit'});
    
    let itemsHtml = '';
    items.forEach(function(item) {
        itemsHtml += '<tr><td>' + item.product_name + '</td><td style="text-align:center">' + item.quantity + '</td><td style="text-align:right">' + currency + ' ' + parseFloat(item.total_price).toFixed(2) + '</td></tr>';
    });
    
    const printWindow = window.open('', '', 'width=400,height=600');
    const html = '<html><head><title>Receipt</title>' +
    '<style>' +
    'body{font-family:Courier,monospace;width:80mm;padding:10px;margin:0 auto}' +
    '@page{margin:0;size:80mm auto}' +
    '.header{text-align:center;margin-bottom:15px;border-bottom:2px dashed #000;padding-bottom:10px}' +
    '.header h2{font-size:18px;margin:5px 0}' +
    '.header p{font-size:11px;margin:2px 0}' +
    '.info{margin:10px 0;font-size:11px}' +
    '.info div{display:flex;justify-content:space-between;margin:3px 0}' +
    '.items{margin:10px 0}' +
    '.items table{width:100%;border-collapse:collapse}' +
    '.items th{text-align:left;border-bottom:1px solid #000;padding:5px 0;font-size:11px}' +
    '.items td{padding:5px 0;font-size:11px}' +
    '.total{margin-top:10px;padding-top:10px;border-top:2px solid #000}' +
    '.total div{display:flex;justify-content:space-between;font-size:14px;font-weight:bold;margin:5px 0}' +
    '.footer{text-align:center;margin-top:15px;padding-top:10px;border-top:2px dashed #000;font-size:10px}' +
    '</style></head><body>' +
    '<div class="header"><h2>' + school + '</h2><p>SALES RECEIPT</p><p>Receipt #' + saleId + '</p></div>' +
    '<div class="info">' +
    '<div><span>Date:</span><span>' + dateFormatted + '</span></div>' +
    '<div><span>Customer:</span><span>' + customer + '</span></div>' +
    '</div>' +
    '<div class="items"><table><thead><tr><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Amount</th></tr></thead><tbody>' +
    itemsHtml +
    '</tbody></table></div>' +
    '<div class="total"><div><span>TOTAL</span><span>' + currency + ' ' + parseFloat(sale.total_amount).toFixed(2) + '</span></div></div>' +
    '<div class="footer"><p>Thank you for your purchase!</p><p>' + slogan + '</p></div>' +
    '</body></html>';
    
    printWindow.document.write(html);
    printWindow.document.close();
    setTimeout(function() { printWindow.print(); }, 500);
}
</script>
