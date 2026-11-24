
<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['login'])) {
    header('location: index.php');
    exit;
}

// Hanya admin yang boleh mengedit akun
if ($_SESSION['role'] !== 'admin') {
    header('location: dashboard.php');
    exit;
}

$type = $_GET['type'] ?? '';
$targetRole = $_GET['role'] ?? '';
$id = $_GET['id'] ?? '';

// Menentukan tabel dan kolom ID
if ($type === 'mahasiswa') {
    $table = 'mahasiswa';
    $idColumn = 'nim';
    $judul = 'Edit Akun Mahasiswa';

} elseif ($type === 'staff') {

    if ($targetRole === 'dosen') {
        $table = 'dosen';
        $idColumn = 'nidn';

    } elseif ($targetRole === 'admin') {
        $table = 'admin';
        $idColumn = 'username';

    } elseif ($targetRole === 'staff') {
        $table = 'staff';
        $idColumn = 'nip';

    } else {
        die('Role tidak valid!');
    }

    $judul = 'Edit Akun ' . ucfirst($targetRole);

} else {
    die('Jenis akun tidak valid!');
}

if ($id === '') {
    die('ID akun tidak ditemukan!');
}

// Cek apakah tabel tersedia
$cekTabel = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");

if (!$cekTabel || mysqli_num_rows($cekTabel) == 0) {
    die('Tabel akun tidak ditemukan!');
}

// Ambil data user
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT nama FROM `$table` WHERE `$idColumn` = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $nama);

if (!mysqli_stmt_fetch($stmt)) {
    die('Data user tidak ditemukan!');
}

mysqli_stmt_close($stmt);

// Proses update
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $namaBaru = trim($_POST['nama'] ?? '');

    if ($namaBaru === '') {
        $pesan = 'Nama tidak boleh kosong!';

    } else {

        $update = mysqli_prepare(
            $koneksi,
            "UPDATE `$table` SET nama = ? WHERE `$idColumn` = ?"
        );

        mysqli_stmt_bind_param($update, "ss", $namaBaru, $id);

        if (mysqli_stmt_execute($update)) {

            header("location: users.php?type=" . urlencode($type));
            exit;

        } else {
            $pesan = 'Gagal memperbarui data!';
        }

        mysqli_stmt_close($update);
    }

    $nama = $namaBaru;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($judul); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow">

        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-pencil-square"></i>
                <?php echo htmlspecialchars($judul); ?>
            </h5>
        </div>

        <div class="card-body">

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">ID User</label>

                    <input type="text"
                           class="form-control"
                           value="<?php echo htmlspecialchars($id); ?>"
                           readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>

                    <input type="text"
                           name="nama"
                           class="form-control"
                           value="<?php echo htmlspecialchars($nama); ?>"
                           required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>

                <a href="users.php?type=<?php echo urlencode($type); ?>"
                   class="btn btn-secondary">
                    Kembali
                </a>

            </form>

        </div>
    </div>

</div>

</body>
</html>
