<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Coffee ERP - Module 1</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 p-6">

<!-- ?php include('includes/sidebar.php'); -->

  <h1 class="text-2xl font-bold mb-6 text-center">Coffee Shop ERP - Inventory Module</h1>

    <!-- Search -->
  <section class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-lg font-semibold mb-4">Search Items</h2>
    <div class="flex space-x-2">
      <input id="q" placeholder="Search by SKU/Name" class="flex-1 border rounded px-3 py-2">
      <button id="searchBtn" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Search</button>
    </div>
    <pre id="results" class="mt-4 bg-gray-100 p-3 rounded text-sm overflow-x-auto"></pre>
  </section>

  <!-- Add Item -->
  <section class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-lg font-semibold mb-4">Inventory - Add Item</h2>
    <form id="addItem" class="space-y-3">
      <input name="sku" placeholder="SKU" class="w-full border rounded px-3 py-2">
      <input name="name" placeholder="Name" class="w-full border rounded px-3 py-2">
      <input name="category" placeholder="Category" class="w-full border rounded px-3 py-2">
      <input name="unit" placeholder="Unit" class="w-full border rounded px-3 py-2">
      <input name="min_threshold" type="number" placeholder="Min Quantity" class="w-full border rounded px-3 py-2">
      <input name="max_threshold" type="number" placeholder="Max Quantity" class="w-full border rounded px-3 py-2">
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Item</button>
    </form>
  </section>

  <!-- Stock In -->
  <section class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-lg font-semibold mb-4">Stock In</h2>
    <form id="stockIn" class="space-y-3">
      <input name="item_id" type="number" placeholder="Item ID" class="w-full border rounded px-3 py-2">
      <input name="warehouse_id" type="number" placeholder="Warehouse ID" value="1" class="w-full border rounded px-3 py-2">
      <input name="quantity" type="number" placeholder="Quantity" class="w-full border rounded px-3 py-2">
      <input name="expiry_date" type="date" class="w-full border rounded px-3 py-2">
      <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Stock In</button>
    </form>
  </section>

  <!-- Stock Out -->
  <section class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-lg font-semibold mb-4">Stock Out</h2>
    <form id="stockOut" class="space-y-3">
      <input name="item_id" type="number" placeholder="Item ID" class="w-full border rounded px-3 py-2">
      <input name="warehouse_id" type="number" placeholder="Warehouse ID" value="1" class="w-full border rounded px-3 py-2">
      <input name="quantity" type="number" placeholder="Quantity" class="w-full border rounded px-3 py-2">
      <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Stock Out</button>
    </form>
  </section>

  <!-- Transfer -->
  <section class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-lg font-semibold mb-4">Transfer</h2>
    <form id="transfer" class="space-y-3">
      <input name="item_id" type="number" placeholder="Item ID" class="w-full border rounded px-3 py-2">
      <input name="warehouse_from" type="number" placeholder="From Warehouse" value="1" class="w-full border rounded px-3 py-2">
      <input name="warehouse_to" type="number" placeholder="To Warehouse" value="2" class="w-full border rounded px-3 py-2">
      <input name="quantity" type="number" placeholder="Quantity" class="w-full border rounded px-3 py-2">
      <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">Transfer</button>
    </form>
  </section>

  <!-- Alerts -->
  <section class="bg-white shadow rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Check Alerts</h2>
    <button id="alertsBtn" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Get Alerts</button>
    <pre id="alertsPre" class="mt-4 bg-gray-100 p-3 rounded text-sm overflow-x-auto"></pre>
  </section>

<script>
function postJson(url, data){
  return fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)})
    .then(r=>r.json());
}

document.getElementById('addItem').onsubmit = e=>{
  e.preventDefault();
  const fd = Object.fromEntries(new FormData(e.target).entries());
  postJson('Module1/add_item.php', fd).then(r=>alert(JSON.stringify(r)));
};

document.getElementById('stockIn').onssubmit = e=>{
  e.preventDefault();
  const fd = Object.fromEntries(new FormData(e.target).entries());
  postJson('Module1/stock_in.php', fd).then(r=>alert(JSON.stringify(r)));
};

document.getElementById('stockOut').onsubmit = e=>{
  e.preventDefault();
  const fd = Object.fromEntries(new FormData(e.target).entries());
  postJson('Module1/stock_out.php', fd).then(r=>alert(JSON.stringify(r)));
};

document.getElementById('transfer').onsubmit = e=>{
  e.preventDefault();
  const fd = Object.fromEntries(new FormData(e.target).entries());
  postJson('Module1/transfer.php', fd).then(r=>alert(JSON.stringify(r)));
};

document.getElementById('searchBtn').onclick = ()=>{
  const q = document.getElementById('q').value;
  fetch('Module1/get_items.php?q='+encodeURIComponent(q))
    .then(r=>r.json()).then(j=>{
      document.getElementById('results').textContent = JSON.stringify(j, null, 2);
    });
};

document.getElementById('alertsBtn').onclick = ()=>{
  fetch('Module1/check_alerts.php').then(r=>r.json()).then(j=>{
    document.getElementById('alertsPre').textContent = JSON.stringify(j, null, 2);
  });
};
</script>
</body>
</html>
