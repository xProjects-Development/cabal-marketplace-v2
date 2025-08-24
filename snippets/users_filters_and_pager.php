<?php
$role  = $_GET['role']  ?? '';
$status= $_GET['status']?? '';
$per   = isset($_GET['per']) ? max(5,(int)$_GET['per']) : 25;
$page  = isset($_GET['page'])? max(1,(int)$_GET['page']) : 1;
$total_users = adminu_users_total($Q, $role, $status);
$offset = ($page-1) * $per;
$users  = adminx_users($Q, $per, $offset); // use your existing fetch
?>
<form method="get" class="flex flex-wrap items-end gap-3 mb-4">
  <input type="hidden" name="tab" value="users">
  <div>
    <label class="block text-xs text-gray-600 mb-1">Role</label>
    <select name="role" class="border rounded px-2 py-1">
      <option value="">All</option>
      <option value="user"  <?= $role==='user'?'selected':'' ?>>user</option>
      <option value="admin" <?= $role==='admin'?'selected':'' ?>>admin</option>
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-600 mb-1">Status</label>
    <select name="status" class="border rounded px-2 py-1">
      <option value="">All</option>
      <option value="active" <?= $status==='active'?'selected':'' ?>>active</option>
      <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>suspended</option>
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-600 mb-1">Per page</label>
    <select name="per" class="border rounded px-2 py-1">
      <?php foreach ([25,50,100,200] as $pp): ?>
        <option value="<?= $pp ?>" <?= $per===$pp?'selected':'' ?>><?= $pp ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="bg-gray-800 text-white px-3 py-2 rounded">Apply</button>
  <a class="px-3 py-2 rounded bg-gray-100" href="admin_export.php?type=users&<?= http_build_query(['q'=>$Q,'role'=>$role,'status'=>$status]) ?>">Export CSV</a>
</form>
<?php adminu_pager($total_users, $per, $page); ?>
