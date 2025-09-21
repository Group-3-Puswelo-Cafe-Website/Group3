<?php
// shared/sidebar.php
require_once __DIR__ . '/config.php';

// --- Ensure $current_page exists ---
if (!isset($current_page) || empty($current_page)) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $current_page = basename($path);
    if (empty($current_page)) {
        $current_page = basename($_SERVER['SCRIPT_NAME']);
    }
}
$current_page = strtolower($current_page);

// helper: check if any of the names matches current page
function is_active($names) {
    global $current_page;
    foreach ((array)$names as $n) {
        if ($current_page === strtolower(basename($n))) return true;
    }
    return false;
}
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>shared/sidebar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<aside class="fixed top-0 left-0 w-60 h-screen bg-gray-100 text-gray-800 flex flex-col shadow-md z-50">
  <!-- Title -->
  <div class="p-4 text-xl font-semibold flex items-center gap-2 border-b border-gray-300">
    <span>Coffee Business</span>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
    <!-- Dashboard -->
    <a href="<?php echo BASE_URL; ?>dashboard.php"
       class="flex items-center gap-2 px-3 py-2 rounded-md transition <?php echo is_active('dashboard.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
       Dashboard
    </a>

    <!-- Inventory Dropdown -->
    <details class="group" <?php if (is_active(['index.php','locations.php','transactions.php','alerts.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Inventory</span>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module1/index.php" class="block px-2 py-1 rounded <?php echo is_active('index.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">Product Item</a>

        <a href="<?php echo BASE_URL; ?>Module1/locations.php" class="block px-2 py-1 rounded <?php echo is_active('locations.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          Manage Warehouse
        </a>

        <a href="<?php echo BASE_URL; ?>Module1/Alerts.php" class="block px-2 py-1 rounded <?php echo is_active('alerts.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          Alerts & Reports
        </a>
      </div>
    </details>
    
    <!-- Procurement Dropdown (for Module 3) -->
    <details class="group" <?php if (is_active(['purchase_requisitions.php','purchase_orders.php','suppliers.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-200">
        <span>Procurement</span>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="<?php echo BASE_URL; ?>Module3/purchase_requisitions.php" class="block px-2 py-1 rounded <?php echo is_active('purchase_requisitions.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">Purchase Requisitions</a>

        <a href="<?php echo BASE_URL; ?>Module3/purchase_orders.php" class="block px-2 py-1 rounded <?php echo is_active('purchase_orders.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          Purchase Orders
        </a>

        <a href="<?php echo BASE_URL; ?>Module3/suppliers.php" class="block px-2 py-1 rounded <?php echo is_active('suppliers.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          Suppliers
        </a>
      </div>
    </details>
  </nav>
</aside>