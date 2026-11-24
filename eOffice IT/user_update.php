<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['login'])) {
    header('location: index.php');
    exit;
}

$role = $_SESSION['role'];
$nama_user = $_SESSION['nama'];

// Admin, dosen, and kaprodi can access
if ($role !== 'admin' && $role !== 'dosen' && $role !== 'kaprodi') {
    header('location: dashboard.php');
    exit;
}

$cekKolomRole = mysqli_query($koneksi, "SHOW COLUMNS FROM dosen LIKE 'role'");
if (!$cekKolomRole || mysqli_num_rows($cekKolomRole) === 0) {
    if (!mysqli_query($koneksi, "ALTER TABLE dosen ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'dosen'")) {
        die('Gagal menambahkan kolom role pada tabel dosen: ' . htmlspecialchars(mysqli_error($koneksi)));
    }
}

$type = isset($_GET['type']) ? $_GET['type'] : 'staff';

if ($type === 'mahasiswa') {
    // include password for admin viewers
    if ($role === 'admin') {
        $query = "SELECT nim AS id, nama, password FROM mahasiswa ORDER BY nama";
    } else {
        $query = "SELECT nim AS id, nama FROM mahasiswa ORDER BY nama";
    }
    $judul = "Daftar Akun Mahasiswa";
} else {
    // staff: gabungkan dosen, admin (jika ada) dan staff
    if ($role === 'admin') {
        $query = "SELECT nidn AS id, nama, `role` AS `role`, password FROM dosen";
    } else {
        $query = "SELECT nidn AS id, nama, `role` AS `role` FROM dosen";
    }
    // cek apakah tabel admin ada
    $cekAdminTable = mysqli_query($koneksi, "SHOW TABLES LIKE 'admin'");
    if (mysqli_num_rows($cekAdminTable) > 0) {
        if ($role === 'admin') {
            $query .= " UNION SELECT username AS id, nama, 'admin' AS `role`, password FROM admin";
        } else {
            $query .= " UNION SELECT username AS id, nama, 'admin' AS `role` FROM admin";
        }
    }
    // include staff table if it exists
    $cekStaffTable = mysqli_query($koneksi, "SHOW TABLES LIKE 'staff'");
    if (mysqli_num_rows($cekStaffTable) > 0) {
        if ($role === 'admin') {
            $query .= " UNION SELECT nip AS id, nama, 'staff' AS `role`, password FROM staff";
        } else {
            $query .= " UNION SELECT nip AS id, nama, 'staff' AS `role` FROM staff";
        }
    }
    $query .= " ORDER BY nama";
    $judul = "Daftar Akun Admin, Dosen, Kaprodi & Staff";
}

$hasil = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $judul; ?> - Sistem Surat Prodi</title>
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
    <!-- reuse same navbar as dashboard -->
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
        <h4><?php echo $judul; ?></h4>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <?php if($role === 'admin'): ?>
                        <th>Password</th>
                    <?php endif; ?>
                    <?php if($type !== 'mahasiswa'): ?>
                        <th>Role</th>
                    <?php endif; ?>
                    <?php if($role === 'admin'): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($hasil)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['nama']; ?></td>
                    <?php if($role === 'admin'): ?>
                        <td><?php echo isset($row['password']) ? $row['password'] : '-'; ?></td>
                    <?php endif; ?>
                    <?php if($type !== 'mahasiswa'): ?>
                        <td><?php echo htmlspecialchars(ucfirst($row['role'])); ?></td>
                    <?php endif; ?>
                    <?php if($role === 'admin'): ?>
                        <?php
                            $editUrl = 'edit_user.php?type=' . urlencode($type) . '&id=' . urlencode($row['id']);
                            if ($type !== 'mahasiswa') {
                                $editUrl .= '&role=' . urlencode($row['role']);
                            }
                        ?>
                        <td><a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($editUrl); ?>">Edit</a></td>
                    <?php endif; ?>
                </tr>
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
