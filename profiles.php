<?php
require_once __DIR__ . '/app/bootstrap.php';
include __DIR__ . '/app/views/partials/header.php';

function column_exists($table, $col){
  $t = db()->real_escape_string($table);
  $c = db()->real_escape_string($col);
  $q = db()->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $q && $q->num_rows > 0;
}

$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'top';
$db = db();

/* optional avatar/bio columns */
$avatarCol = null; foreach (['avatar_path','avatar_url','avatar'] as $c){ if (column_exists('users',$c)){ $avatarCol = $c; break; } }
$bioCol    = null; foreach (['bio','about'] as $c){ if (column_exists('users',$c)){ $bioCol = $c; break; } }

$sql = "SELECT u.id,u.username,u.first_name,u.last_name,u.created_at,
        COALESCE(AVG(pf.rating),0) AS avg_rating,
        COUNT(DISTINCT pf.id) AS rate_count,
        COUNT(DISTINCT n.id) AS nft_count";

if ($avatarCol) $sql .= ", u.`{$avatarCol}` AS avatar";
if ($bioCol)    $sql .= ", u.`{$bioCol}` AS bio";

$sql .= " FROM users u
          LEFT JOIN profile_feedback pf ON pf.profile_user_id=u.id
          LEFT JOIN nfts n ON n.creator_user_id=u.id ";

$params = []; $types = '';
if ($q !== '') {
  $sql .= "WHERE u.status='active' AND (u.username LIKE CONCAT('%',?,'%') OR u.first_name LIKE CONCAT('%',?,'%') OR u.last_name LIKE CONCAT('%',?,'%')) ";
  $types = 'sss'; $params = [$q,$q,$q];
} else {
  $sql .= "WHERE u.status='active' ";
}
$sql .= "GROUP BY u.id ";
if ($sort==='new') { $sql .= "ORDER BY u.created_at DESC "; }
elseif ($sort==='nfts') { $sql .= "ORDER BY nft_count DESC "; }
else { $sql .= "ORDER BY avg_rating DESC, rate_count DESC "; }
$sql .= "LIMIT 200";

$stmt = $db->prepare($sql);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$res = $stmt->get_result();
$users = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<section class="max-w-7xl mx-auto px-4 py-12">
  <div class="text-center mb-10">
    <h1 class="text-4xl font-bold mb-2">Explore Creators</h1>
    <p class="text-gray-600">Browse user profiles, ratings, and collections.</p>
  </div>
  <form class="bg-white rounded-xl shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search username or name..." class="px-4 py-2 border rounded-lg md:col-span-2">
    <select name="sort" class="px-4 py-2 border rounded-lg">
      <option value="top" <?= $sort==='top'?'selected':'' ?>>Top rated</option>
      <option value="nfts" <?= $sort==='nfts'?'selected':'' ?>>Most NFTs</option>
      <option value="new" <?= $sort==='new'?'selected':'' ?>>Newest</option>
    </select>
    <button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">Apply</button>
  </form>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($users as $u): $full=$u['first_name'].' '.$u['last_name']; $stars = (int)floor($u['avg_rating']); ?>
      <a href="<?= e(BASE_URL) ?>/profile.php?u=<?= e($u['username']) ?>" class="block bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden">
        <div class="p-6 flex items-center gap-4">
<div class="w-16 h-16 rounded-full overflow-hidden bg-gradient-to-br from-purple-400 to-pink-400 text-white flex items-center justify-center text-2xl font-bold">
  <?php if (!empty($u['avatar'])): ?>
    <img src="<?= e($u['avatar']) ?>" class="w-full h-full object-cover" alt="<?= e($u['username']) ?>">
  <?php else: ?>
    <?= e(strtoupper($u['first_name'][0] ?? $u['username'][0] ?? 'U')) ?>
  <?php endif; ?>
</div>

          <div class="flex-1">
            <div class="font-semibold text-gray-900"><?= e($full) ?></div>
            <div class="text-sm text-gray-600">@<?= e($u['username']) ?></div>
            <div class="flex items-center gap-2 mt-1">
              <div class="text-yellow-500">
                <?php for($i=1;$i<=5;$i++){ echo '<i class="fas fa-star'.($i<=$stars?'':'-o').'"></i>'; } ?>
              </div>
              <div class="text-xs text-gray-600">(<?= (int)$u['rate_count'] ?>)</div>
              <div class="text-xs text-gray-500 ml-auto"><?= (int)$u['nft_count'] ?> NFTs</div>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/app/views/partials/footer.php'; ?>
