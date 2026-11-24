<?php
session_start();
include 'koneksi.php';

// ensure column to track resend existence
mysqli_query($koneksi, "ALTER TABLE surat ADD COLUMN IF NOT EXISTS resend_done TINYINT(1) NOT NULL DEFAULT 0 AFTER status");

if (!isset($_SESSION['login'])) {
    header("location: index.php");
    exit;
}

$id_user = $_SESSION['id'];
$role = $_SESSION['role'];
$nama_user = $_SESSION['nama'];

// Ambil semua surat (masuk & keluar)
$query_surat = "SELECT * FROM surat WHERE pengirim_id = '$id_user' OR penerima_id = '$id_user' ORDER BY id_surat DESC";
$hasil_surat = mysqli_query($koneksi, $query_surat);

// Ambil status surat yang perlu dikonfirmasi
$query_status = "SELECT * FROM surat WHERE penerima_id = '$id_user' AND status = 'menunggu'";
$surat_menunggu = mysqli_query($koneksi, $query_status);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Prodi</title>
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
            <a class="navbar-brand" href="dashboard.php">📬 eOffice TI</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if($role == 'admin' || $role == 'dosen'): ?>
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
        <!-- Notifikasi Status -->
        <?php if (mysqli_num_rows($surat_menunggu) > 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            Ada <strong><?php echo mysqli_num_rows($surat_menunggu); ?></strong> surat menunggu konfirmasi!
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Kolom Kiri: Buat Surat -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-plus-circle"></i> Buat Surat Baru
                    </div>
                    <div class="card-body">
                        <form action="proses_surat.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-2">
                                <label>Penerima (NIM/NIDN/NIP)</label>
                                <input type="text" name="penerima" class="form-control" required placeholder="Contoh: 2355202016">
                            </div>
                            <div class="mb-2">
                                <label>Perihal</label>
                                <input type="text" name="perihal" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label>Isi/Keterangan Surat</label>
                                <textarea name="isi" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="mb-2">
                                <label>File (PDF, opsional)</label>
                                <input type="file" name="file" accept="application/pdf" class="form-control">
                            </div>
                            <button type="submit" name="kirim" class="btn btn-success w-100">
                                <i class="bi bi-send"></i> Kirim Surat
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Daftar Surat dengan Status -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-inbox"></i> Daftar Surat
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Tipe</th>
                                    <th>Dari</th>
                                    <th>Perihal</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                while($row = mysqli_fetch_assoc($hasil_surat)): 
                                    $tipe = ($row['pengirim_id'] == $id_user) ? 'Keluar' : 'Masuk';
                                    $warna_status = '';
                                    $icon_status = '';
                                    
                                    switch($row['status']) {
                                        case 'menunggu':
                                            $warna_status = 'bg-warning text-dark';
                                            $icon_status = '⏳';
                                            break;
                                        case 'dikonfirmasi':
                                            $warna_status = 'bg-success text-white';
                                            $icon_status = '✅';
                                            break;
                                        case 'dikembalikan':
                                            $warna_status = 'bg-danger text-white';
                                            $icon_status = '❌';
                                            break;
                                    }
                                ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo $tipe; ?></span></td>
                                    <td><?php echo $row['pengirim_id'] == $id_user ? 'Saya' : $row['penerima_id']; ?></td>
                                    <td><?php echo $row['perihal']; ?></td>
                                    <td><?php echo $row['tanggal']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $warna_status; ?>">
                                            <?php echo $icon_status; ?> <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- always allow reading; inside modal we can confirm/return if needed -->
                                        <?php if ($row['status'] == 'dikembalikan' && $row['pengirim_id'] == $id_user && $role == 'mahasiswa' && empty($row['resend_done'])): ?>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalResend<?php echo $row['id_surat']; ?>">
                                                <i class="bi bi-arrow-clockwise"></i> Kirim Ulang
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalLihat<?php echo $row['id_surat']; ?>">
                                            <i class="bi bi-eye"></i> Baca
                                        </button>
                                    </td>
                                </tr>



                                <!-- Modal Lihat Surat -->
                                <div class="modal fade" id="modalLihat<?php echo $row['id_surat']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo $row['perihal']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Dari:</strong> <?php echo $row['pengirim_id']; ?></p>
                                                <p><strong>Status:</strong> <span class="badge <?php echo $warna_status; ?>"><?php echo $row['status']; ?></span></p>
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
                                            <div class="modal-footer">
                                                <?php if ($row['status'] == 'menunggu' && $row['penerima_id'] == $id_user && ($role=='admin' || $role=='dosen')): ?>
                                                    <form action="proses_surat.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="id_surat" value="<?php echo $row['id_surat']; ?>">
                                                        <input type="hidden" name="status" value="dikonfirmasi">
                                                        <button type="submit" name="update_status" class="btn btn-success">Konfirmasi</button>
                                                    </form>
                                                    <!-- trigger return reason modal -->
                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalReason<?php echo $row['id_surat']; ?>">
                                                        Kembalikan
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- return reason modal -->
                                <div class="modal fade" id="modalReason<?php echo $row['id_surat']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Kembalikan Surat</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="proses_surat.php" method="POST">
                                                    <input type="hidden" name="id_surat" value="<?php echo $row['id_surat']; ?>">
                                                    <input type="hidden" name="status" value="dikembalikan">
                                                    <div class="mb-2">
                                                        <label>Alasan pengembalian</label>
                                                        <textarea name="catatan" class="form-control" rows="3" required></textarea>
                                                    </div>
                                                    <button type="submit" name="update_status" class="btn btn-danger">Kembalikan</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- resend modal for mahasiswa -->
                                <?php if ($row['status'] == 'dikembalikan' && $row['pengirim_id'] == $id_user && $role == 'mahasiswa'): ?>
                                <div class="modal fade" id="modalResend<?php echo $row['id_surat']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title">Kirim Ulang Surat</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="proses_surat.php" method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="resend_original" value="<?php echo $row['id_surat']; ?>">
                                                    <div class="mb-2">
                                                        <label>Perihal</label>
                                                        <input type="text" name="perihal" class="form-control" value="Perubahan surat yang dikembalikan" readonly>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label>File (PDF)</label>
                                                        <input type="file" name="file" accept="application/pdf" class="form-control" required>
                                                    </div>
                                                    <button type="submit" name="resend" class="btn btn-warning">Kirim Ulang</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <footer class="text-center text-muted small container">
        <hr>
        <strong>Copyright eOffice IT © 2026 Prodi Teknik Informatika. All rights reserved.</strong>
    </footer>
</body>
</html>