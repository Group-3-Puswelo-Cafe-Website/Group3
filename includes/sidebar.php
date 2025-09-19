<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<aside class="fixed top-0 left-0 w-60 h-screen bg-gray-100 text-gray-800 flex flex-col shadow-md z-50">
  <!-- Title -->
  <div class="p-4 text-xl font-semibold flex items-center gap-2 border-b border-gray-300">
    <span>Coffee Business</span>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
    <!-- Dashboard -->
    <a href="dashboard.php" 
       class="flex items-center gap-2 px-3 py-2 rounded-md transition 
       <?php echo ($current_page == 'dashboard.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
       Dashboard
    </a>

    <!-- Inventory Dropdown -->
    <details class="group" <?php if(in_array($current_page, ['items.php','stockin.php','stockout.php'])) echo "open"; ?>>
      <summary class="flex items-center justify-between px-3 py-2 rounded-md cursor-pointer hover:bg-gray-200">
        <span>Inventory</span>
        <span>
          <i class="fa fa-angle-down group-open:inline"></i>
        </span>
      </summary>
      <div class="ml-5 mt-1 space-y-1">
        <a href="items.php" class="block px-2 py-1 rounded 
          <?php echo ($current_page == 'items.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          Items
        </a>
        <a href="stockin.php" class="block px-2 py-1 rounded 
          <?php echo ($current_page == 'stockin.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          Stock In
        </a>
        <a href="stockout.php" class="block px-2 py-1 rounded 
          <?php echo ($current_page == 'stockout.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-200'; ?>">
          Stock Out
        </a>
      </div>
    </details>
  </nav>
</aside>
