<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit();
}

require_once '../classes/Database.php';
require_once '../classes/Mahasiswa.php';

 $db = new Database();
 $mahasiswa = new Mahasiswa($db, $_SESSION['user_id']);

// Logika untuk mengambil data laporan (misal untuk 7 hari terakhir)
 $report_data = [];
 $labels = [];
 $kalori_data = [];
 $protein_data = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($date));
    
    $db->query("SELECT SUM(mm.kalori) as total_kalori, SUM(mm.protein) as total_protein FROM jadwal_makan jm JOIN menu_makanan mm ON jm.menu_id = mm.id WHERE jm.mahasiswa_id = :mhs_id AND jm.tanggal = :date");
    $db->bind('mhs_id', $mahasiswa->getMahasiswaId());
    $db->bind('date', $date);
    $result = $db->single();

    $kalori_data[] = $result['total_kalori'] ?? 0;
    $protein_data[] = $result['total_protein'] ?? 0;
}

// Log aktivitas
 $user = new User($db);
 $user->logActivity("Melihat laporan evaluasi.");

include '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Laporan Evaluasi Gizi</h1>
    <p>Grafik asupan kalori dan protein Anda selama 7 hari terakhir.</p>
    
    <div class="card">
        <div class="card-body">
            <canvas id="nutritionChart" width="400" height="150"></canvas>
        </div>
    </div>
</div>

<!-- Script untuk Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('nutritionChart').getContext('2d');
    const nutritionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Asupan Kalori (kcal)',
                data: <?php echo json_encode($kalori_data); ?>,
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 2,
                fill: false,
                yAxisID: 'y'
            }, {
                label: 'Asupan Protein (g)',
                data: <?php echo json_encode($protein_data); ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: false,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Kalori (kcal)' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Protein (g)' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>