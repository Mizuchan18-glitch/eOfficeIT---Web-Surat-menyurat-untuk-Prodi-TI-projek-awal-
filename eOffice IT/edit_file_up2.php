<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header('location: index.php');
    exit;
}

$id_user = $_SESSION['id'];
$role = $_SESSION['role'];
$nama_user = $_SESSION['nama'];

// surat yang sudah dikonfirmasi (sudah selesai)
$query = "SELECT * FROM surat WHERE status = 'dikonfirmasi' AND (pengirim_id = '$id_user' OR penerima_id = '$id_user') ORDER BY id_surat DESC";
$hasil = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Surat - Sistem Surat Prodi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { display: flex; flex-direction: column; }
        .main-container { flex: 1; }
        footer { padding: 0.5rem 0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">📬 to Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if($role == 'admin' || $role == 'dosen' || $role == 'kaprodi'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            User
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="users.php?type=staff">Admin &amp; Dosen</a></li>
                            <li><a class="dropdown-item" href="users.php?type=mahasiswa">Mahasiswa</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="persuratanMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Persuratan
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="persuratanMenu">
                            <li><a class="dropdown-item" href="surat_masuk.php">Surat Masuk</a></li>
                            <li><a class="dropdown-item" href="surat_keluar.php">Surat Keluar</a></li>
                            <li><a class="dropdown-item" href="riwayat.php">Riwayat</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="profileMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Halo, <?php echo $nama_user; ?> (<?php echo $role; ?>)
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileMenu">
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <h4>Riwayat Surat (Terkonfirmasi)</h4>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Tipe</th>
                    <th>Pengirim / Penerima</th>
                    <th>Perihal</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($hasil)): 
                    $tipe = ($row['pengirim_id'] == $id_user) ? 'Keluar' : 'Masuk';
                ?>
                <tr>
                    <td><span class="badge bg-secondary"><?php echo $tipe; ?></span></td>
                    <td><?php echo $tipe == 'Keluar' ? $row['penerima_id'] : $row['pengirim_id']; ?></td>
                    <td><?php echo $row['perihal']; ?></td>
                    <td><?php echo $row['tanggal']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalLihat<?php echo $row['id_surat']; ?>">
                            <i class="bi bi-eye"></i> Baca
                        </button>
                    </td>
                </tr>

                <div class="modal fade" id="modalLihat<?php echo $row['id_surat']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo $row['perihal']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Dari:</strong> <?php echo $row['pengirim_id']; ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-success text-white">dikonfirmasi</span></p>
                                <?php if ($row['catatan_status']): ?>
                                    <hr>
                                    <p><strong>Catatan:</strong> <?php echo $row['catatan_status']; ?></p>
                                <?php endif; ?>
                                <hr>
                                <p><?php echo nl2br($row['isi_surat']); ?></p>
                                <?php if (!empty($row['file_path'])): ?>
                                    <hr>
                                    <p><strong>File:</strong> <a href="<?php echo $row['file_path']; ?>" target="_blank">Download PDF</a></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <footer class="text-center text-muted small container">
        <hr>
        <strong>Copyright eOffice IT © 2026 Prodi Teknik Informatika. All rights reserved.</strong>
    </footer>    
</body>
</html>
