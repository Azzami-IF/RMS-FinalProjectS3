<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Admin.php';
require_once '../classes/SpoonacularAPI.php';

 $db = new Database();
 $admin = new Admin($db);
 $spoonacular = new SpoonacularAPI(SPOONACULAR_API_KEY);

// Logika CRUD
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $db->query("DELETE FROM menu_makanan WHERE id = :id");
    $db->bind('id', $id);
    $db->execute();
    $admin->logActivity("Admin menghapus menu dengan ID $id");
    header('Location: manage_menu.php');
    exit();
}

// Ambil semua menu dari database
 $db->query("SELECT * FROM menu_makanan ORDER BY nama_makanan ASC");
 $menus = $db->resultSet();

// Log aktivitas
 $admin->logActivity("Admin mengakses halaman kelola menu.");

include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Kelola Menu Makanan</h1>
    <a href="#" class="btn btn-success mb-3" data-toggle="modal" data-target="#addMenuModal">+ Tambah Menu Baru (dari Spoonacular)</a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Makanan</th>
                <th>Kalori</th>
                <th>Protein (g)</th>
                <th>Karbohidrat (g)</th>
                <th>Lemak (g)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($menus as $menu): ?>
            <tr>
                <td><?php echo $menu['id']; ?></td>
                <td><?php echo htmlspecialchars($menu['nama_makanan']); ?></td>
                <td><?php echo $menu['kalori']; ?></td>
                <td><?php echo $menu['protein']; ?></td>
                <td><?php echo $menu['karbohidrat']; ?></td>
                <td><?php echo $menu['lemak']; ?></td>
                <td>
                    <a href="?delete=<?php echo $menu['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah Menu -->
<div class="modal fade" id="addMenuModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Menu dari Spoonacular</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="searchForm">
                    <div class="form-group">
                        <label for="searchQuery">Cari Makanan</label>
                        <input type="text" class="form-control" id="searchQuery" placeholder="Contoh: apple">
                    </div>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
                <div id="searchResults" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const query = document.getElementById('searchQuery').value;
    const resultsDiv = document.getElementById('searchResults');
    
    // Di sini Anda seharusnya memanggil endpoint PHP untuk mencari API
    // Untuk kesederhanaan, kita anggap ada endpoint /process/search_food.php
    const response = await fetch(`../process/search_food.php?q=${encodeURIComponent(query)}`);
    const results = await response.json();
    
    let html = '<h6>Hasil Pencarian:</h6><ul class="list-group">';
    results.forEach(item => {
        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    ${item.name}
                    <button class="btn btn-sm btn-primary" onclick="addMenuToDB(${item.id}, '${item.name}')">Tambah</button>
                 </li>`;
    });
    html += '</ul>';
    resultsDiv.innerHTML = html;
});

async function addMenuToDB(spoonacularId, name) {
    // Panggil endpoint PHP untuk menambah ke database
    const response = await fetch(`../process/add_menu_from_api.php?id=${spoonacularId}`);
    const result = await response.json();
    if(result.success) {
        alert(`Menu ${name} berhasil ditambahkan!`);
        location.reload(); // Reload untuk melihat perubahan
    } else {
        alert('Gagal menambahkan menu.');
    }
}
</script>

<?php include '../includes/footer.php'; ?>