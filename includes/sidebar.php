<?php
// sidebar.php

// detect current file
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-60 h-screen bg-gray-900 text-gray-200 flex flex-col">
  <!-- Title -->
  <div class="p-4 text-xl font-semibold flex items-center gap-2">
     <span>Coffee Business</span>
  </div>

  <!-- Nav -->
  <nav class="flex-1 px-2 space-y-1">
    <!-- Dashboard -->
    <a href="dashboard.php" 
       class="flex items-center gap-2 px-3 py-2 rounded-md 
       <?php echo ($current_page == 'dashboard.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-800'; ?>">
       🏠 Dashboard
    </a>

    <!-- Inventory -->
    <details class="group" <?php if(in_array($current_page, ['items.php','stockin.php','stockout.php'])) echo "open"; ?>>
      <summary class="flex items-center gap-2 px-3 py-2 rounded-md cursor-pointer hover:bg-gray-800">
        📦 Inventory
      </summary>
      <div class="ml-8 mt-1 space-y-1">
        <a href="items.php" class="block px-2 py-1 rounded 
          <?php echo ($current_page == 'items.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-800'; ?>">
          Items
        </a>
        <a href="stockin.php" class="block px-2 py-1 rounded 
          <?php echo ($current_page == 'stockin.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-800'; ?>">
          Stock In
        </a>
        <a href="stockout.php" class="block px-2 py-1 rounded 
          <?php echo ($current_page == 'stockout.php') ? 'bg-red-600 text-white' : 'hover:bg-gray-800'; ?>">
          Stock Out
        </a>
      </div>
    </details>
  </nav>
</aside>
