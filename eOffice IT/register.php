<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['login'])) {
    header('location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $nama = trim($_POST['nama']);
    $status = $_POST['status'];
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);

    if ($password !== $confirm) {
        $error = 'Password dan konfirmasi tidak cocok.';
    } else {
        if ($status === 'Mahasiswa') {
            $nim = trim($_POST['nim']);
            // pastikan kolom password ada
            mysqli_query($koneksi, "ALTER TABLE mahasiswa ADD COLUMN IF NOT EXISTS password VARCHAR(100) NOT NULL DEFAULT ''");
            // cek duplikat
            $cek = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
            if (mysqli_num_rows($cek) > 0) {
                $error = 'NIM sudah terdaftar.';
            } else {
                $query = "INSERT INTO mahasiswa (nim, nama, password) VALUES ('$nim', '$nama', '$password')";
                if (mysqli_query($koneksi, $query)) {
                    header('location: index.php?registered=1');
                    exit;
                } else {
                    $error = 'Gagal mendaftar: ' . mysqli_error($koneksi);
                }
            }
        } elseif ($status === 'Dosen') {
            $nidn = trim($_POST['nidn']);
            // pastikan kolom password ada
            mysqli_query($koneksi, "ALTER TABLE dosen ADD COLUMN IF NOT EXISTS password VARCHAR(100) NOT NULL DEFAULT ''");
            $cek = mysqli_query($koneksi, "SELECT * FROM dosen WHERE nidn = '$nidn'");
            if (mysqli_num_rows($cek) > 0) {
                $error = 'NIDN sudah terdaftar.';
            } else {
                $query = "INSERT INTO dosen (nidn, nama, password) VALUES ('$nidn', '$nama', '$password')";
                if (mysqli_query($koneksi, $query)) {
                    header('location: index.php?registered=1');
                    exit;
                } else {
                    $error = 'Gagal mendaftar: ' . mysqli_error($koneksi);
                }
            }
        } elseif ($status === 'Staff') {
            $nip = trim($_POST['nip']);
            // pastikan tabel dan kolom password ada
            mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS staff (
                nip VARCHAR(50) NOT NULL PRIMARY KEY,
                nama VARCHAR(100) NOT NULL,
                password VARCHAR(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            mysqli_query($koneksi, "ALTER TABLE staff ADD COLUMN IF NOT EXISTS password VARCHAR(100) NOT NULL DEFAULT ''");
            $cek = mysqli_query($koneksi, "SELECT * FROM staff WHERE nip = '$nip'");
            if (mysqli_num_rows($cek) > 0) {
                $error = 'NIP sudah terdaftar.';
            } else {
                $query = "INSERT INTO staff (nip, nama, password) VALUES ('$nip', '$nama', '$password')";
                if (mysqli_query($koneksi, $query)) {
                    header('location: index.php?registered=1');
                    exit;
                } else {
                    $error = 'Gagal mendaftar: ' . mysqli_error($koneksi);
                }
            }
        } else {
            $error = 'Status tidak valid.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Akun - Prodi Surat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleFields() {
            var status = document.getElementById('status').value;
            var nimGroup = document.getElementById('nimGroup');
            var nidnGroup = document.getElementById('nidnGroup');
            var nipGroup = document.getElementById('nipGroup');
            var nimInput = document.querySelector('input[name="nim"]');
            var nidnInput = document.querySelector('input[name="nidn"]');
            var nipInput = document.querySelector('input[name="nip"]');
            if (status === 'Mahasiswa') {
                nimGroup.style.display = 'block';
                nidnGroup.style.display = 'none';
                nipGroup.style.display = 'none';
                nimInput.required = true;
                nidnInput.required = false;
                nipInput.required = false;
            } else if (status === 'Dosen') {
                nimGroup.style.display = 'none';
                nidnGroup.style.display = 'block';
                nipGroup.style.display = 'none';
                nimInput.required = false;
                nidnInput.required = true;
                nipInput.required = false;
            } else if (status === 'Staff') {
                nimGroup.style.display = 'none';
                nidnGroup.style.display = 'none';
                nipGroup.style.display = 'block';
                nimInput.required = false;
                nidnInput.required = false;
                nipInput.required = true;
            } else {
                nimGroup.style.display = 'none';
                nidnGroup.style.display = 'none';
                nipGroup.style.display = 'none';
                nimInput.required = false;
                nidnInput.required = false;
            }
        }
    </script>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card p-4 shadow" style="width: 400px;">
        <h4 class="text-center mb-4">📬 Daftar Akun</h4>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" onsubmit="return confirm('Yakin data sudah benar?');">
            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Status</label>
                <select name="status" id="status" class="form-select" onchange="toggleFields()" required>
                    <option value="Mahasiswa">Mahasiswa</option>
                    <option value="Dosen">Dosen</option>
                    <option value="Staff">Staff</option>
                </select>
            </div>
            <div class="mb-3" id="nimGroup">
                <label>NIM</label>
                <input type="number" name="nim" class="form-control">
            </div>
            <div class="mb-3" id="nidnGroup" style="display:none;">
                <label>NIDN</label>
                <input type="number" name="nidn" class="form-control">
            </div>
            <div class="mb-3" id="nipGroup" style="display:none;">
                <label>NIP</label>
                <input type="number" name="nip" class="form-control">
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" name="register" class="btn btn-success w-100">Daftar</button>
        </form>
        <p class="text-center mt-3">
            Sudah punya akun? <a href="index.php">Login di sini</a>
        </p>
        <hr>
        <p class="text-center text-muted small">
            <!-- <strong>Demo Login:</strong><br>
            NIM (mahasiswa): 12345 | Pass: 12345<br>
            NIDN (dosen): 98765 | Pass: 98765<br>
            Username (admin): admin | Pass: admin -->
            <strong>Copyright eOffice IT © 2026 Prodi Teknik Informatika. All rights reserved.</strong><br>
            Any questions? Contact the administrator.<br>
            WhatsApp <a href="https://wa.me/message/LBJLRUKGKSYVM1" target="_blank">+62 853‑5606‑2877</a><br>
            or Instagram <a href="https://www.instagram.com/himanikka_uisb" target="_blank">@himanikka_uisb</a>
        </p>
    </div>
    <script>toggleFields();</script>
</body>
</html>