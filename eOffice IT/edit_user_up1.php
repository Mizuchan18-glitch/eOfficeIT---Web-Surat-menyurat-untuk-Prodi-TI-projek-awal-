
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
    $targetRole = 'mahasiswa';

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

$roleAccounts = [
    'admin' => ['table' => 'admin', 'idColumn' => 'username'],
    'dosen' => ['table' => 'dosen', 'idColumn' => 'nidn'],
    'staff' => ['table' => 'staff', 'idColumn' => 'nip']
];

// Cek apakah tabel tersedia
$cekTabel = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");

if (!$cekTabel || mysqli_num_rows($cekTabel) == 0) {
    die('Tabel akun tidak ditemukan!');
}

// Ambil data user
$selectColumns = $table === 'dosen' ? 'nama, password, prodi' : 'nama, password';
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT $selectColumns FROM `$table` WHERE `$idColumn` = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$passwordSaatIni = '';
$prodi = null;
if ($table === 'dosen') {
    mysqli_stmt_bind_result($stmt, $nama, $passwordSaatIni, $prodi);
} else {
    mysqli_stmt_bind_result($stmt, $nama, $passwordSaatIni);
}

if (!mysqli_stmt_fetch($stmt)) {
    die('Data user tidak ditemukan!');
}

mysqli_stmt_close($stmt);

// Proses update
$pesan = '';
$roleForm = $targetRole;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $namaBaru = trim($_POST['nama'] ?? '');
    $passwordBaru = trim($_POST['password'] ?? '');
    $roleBaru = $type === 'staff' ? ($_POST['role'] ?? '') : $targetRole;
    $roleForm = $roleBaru;

    if ($namaBaru === '') {
        $pesan = 'Nama tidak boleh kosong!';

    } elseif ($type === 'staff' && !isset($roleAccounts[$roleBaru])) {
        $pesan = 'Role tidak valid!';

    } else {
        $roleBerubah = $type === 'staff' && $roleBaru !== $targetRole;

        if ($roleBerubah) {
            $akunTujuan = $roleAccounts[$roleBaru];
            $cekTabelTujuan = mysqli_query($koneksi, "SHOW TABLES LIKE '{$akunTujuan['table']}'");

            if (!$cekTabelTujuan || mysqli_num_rows($cekTabelTujuan) === 0) {
                $pesan = 'Tabel akun tujuan tidak ditemukan!';
            } elseif ($table === 'dosen' && trim((string) $prodi) !== '') {
                $pesan = 'Role tidak dapat diubah karena data prodi dosen tidak bisa dipindahkan.';
            } else {
                $cekId = mysqli_prepare(
                    $koneksi,
                    "SELECT 1 FROM `{$akunTujuan['table']}` WHERE `{$akunTujuan['idColumn']}` = ? LIMIT 1"
                );
                mysqli_stmt_bind_param($cekId, "s", $id);
                mysqli_stmt_execute($cekId);
                mysqli_stmt_store_result($cekId);
                $idSudahAda = mysqli_stmt_num_rows($cekId) > 0;
                mysqli_stmt_close($cekId);

                if ($idSudahAda) {
                    $pesan = 'ID tersebut sudah digunakan pada role tujuan!';
                } else {
                    mysqli_begin_transaction($koneksi);

                    $passwordSimpan = $passwordBaru !== '' ? $passwordBaru : $passwordSaatIni;
                    if ($roleBaru === 'dosen') {
                        $insert = mysqli_prepare(
                            $koneksi,
                            "INSERT INTO dosen (nidn, nama, prodi, password) VALUES (?, ?, NULL, ?)"
                        );
                        mysqli_stmt_bind_param($insert, "sss", $id, $namaBaru, $passwordSimpan);
                    } else {
                        $insert = mysqli_prepare(
                            $koneksi,
                            "INSERT INTO `{$akunTujuan['table']}` (`{$akunTujuan['idColumn']}`, nama, password) VALUES (?, ?, ?)"
                        );
                        mysqli_stmt_bind_param($insert, "sss", $id, $namaBaru, $passwordSimpan);
                    }

                    $berhasil = mysqli_stmt_execute($insert);
                    mysqli_stmt_close($insert);

                    if ($berhasil) {
                        $hapus = mysqli_prepare(
                            $koneksi,
                            "DELETE FROM `$table` WHERE `$idColumn` = ?"
                        );
                        mysqli_stmt_bind_param($hapus, "s", $id);
                        $berhasil = mysqli_stmt_execute($hapus);
                        mysqli_stmt_close($hapus);
                    }

                    if ($berhasil) {
                        mysqli_commit($koneksi);
                        header("location: users.php?type=staff");
                        exit;
                    }

                    mysqli_rollback($koneksi);
                    $pesan = 'Gagal memperbarui data!';
                }
            }
        } else {
            if ($passwordBaru !== '') {
                $update = mysqli_prepare(
                    $koneksi,
                    "UPDATE `$table` SET nama = ?, password = ? WHERE `$idColumn` = ?"
                );
                mysqli_stmt_bind_param($update, "sss", $namaBaru, $passwordBaru, $id);
            } else {
                $update = mysqli_prepare(
                    $koneksi,
                    "UPDATE `$table` SET nama = ? WHERE `$idColumn` = ?"
                );
                mysqli_stmt_bind_param($update, "ss", $namaBaru, $id);
            }

            if (mysqli_stmt_execute($update)) {
                header("location: users.php?type=" . urlencode($type));
                exit;
            }

            $pesan = 'Gagal memperbarui data!';
            mysqli_stmt_close($update);
        }

    }

    $nama = $namaBaru;
    if ($passwordBaru !== '') {
        $passwordSaatIni = $passwordBaru;
    }
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

                <?php if ($type === 'staff'): ?>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <?php foreach (['admin', 'dosen', 'staff'] as $roleOption): ?>
                                <option value="<?php echo htmlspecialchars($roleOption); ?>"
                                    <?php echo $roleForm === $roleOption ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($roleOption); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="Mahasiswa" readonly>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password"
                           name="password"
                           class="form-control"
                           placeholder="Kosongkan jika tidak ingin mengubah password">
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
