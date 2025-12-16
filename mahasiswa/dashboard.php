<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Mahasiswa.php';

 $db = new Database();
 $mahasiswa = new Mahasiswa($db, $_SESSION['user_id']);

// Contoh: Tampilkan notifikasi
 $db->query("SELECT * FROM notifikasi WHERE mahasiswa_id = :id AND is_read = 0 ORDER BY created_at DESC");
 $db->bind('id', $mahasiswa->getMahasiswaId());
 $notifications = $db->resultSet();

// Cek jadwal hari ini
 $db->query("SELECT COUNT(*) as total FROM jadwal_makan WHERE mahasiswa_id = :mhs_id AND tanggal = CURDATE()");
 $db->bind('mhs_id', $mahasiswa->getMahasiswaId());
 $check = $db->single();

if ($check['total'] == 0) {
    // Cek apakah notifikasi untuk ini sudah ada hari ini
    $db->query("SELECT COUNT(*) as total FROM notifikasi WHERE mahasiswa_id = :mhs_id AND DATE(created_at) = CURDATE() AND pesan LIKE '%jangan lupa membuat jadwal%'");
    $db->bind('mhs_id', $mahasiswa->getMahasiswaId());
    $notif_check = $db->single();

    if ($notif_check['total'] == 0) {
        // Buat notifikasi baru
        $pesan = "Pengingat: Anda belum membuat jadwal makan hari ini. Jangan lupa untuk mencatat asupan Anda!";
        $db->query("INSERT INTO notifikasi (mahasiswa_id, pesan) VALUES (:mhs_id, :pesan)");
        $db->bind('mhs_id', $mahasiswa->getMahasiswaId());
        $db->bind('pesan', $pesan);
        $db->execute();
    }
}
 
// Tandai notifikasi sebagai sudah dibaca
if(!empty($notifications)) {
    $db->query("UPDATE notifikasi SET is_read = 1 WHERE mahasiswa_id = :id");
    $db->bind('id', $mahasiswa->getMahasiswaId());
    $db->execute();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Dashboard Mahasiswa</h1>
    <p>Selamat datang, <strong><?php echo $mahasiswa->getNamaLengkap(); ?></strong>!</p>

    <h3>Notifikasi</h3>
    <?php if (empty($notifications)): ?>
        <p>Tidak ada notifikasi baru.</p>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($notifications as $notif): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($notif['pesan']); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <hr>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Buat Jadwal Makan</h5>
                    <p class="card-text">Atur jadwal dan pola makan harian Anda.</p>
                    <a href="jadwal.php" class="btn btn-primary">Buat Jadwal</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Laporan Evaluasi</h5>
                    <p class="card-text">Lihat grafik asupan nutrisi Anda.</p>
                    <a href="laporan.php" class="btn btn-success">Lihat Laporan</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Riwayat Aktivitas</h5>
                    <p class="card-text">Lihat log aktivitas Anda.</p>
                    <a href="riwayat.php" class="btn btn-info">Lihat Riwayat</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>