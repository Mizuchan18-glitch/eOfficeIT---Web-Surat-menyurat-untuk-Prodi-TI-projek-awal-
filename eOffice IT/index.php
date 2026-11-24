<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['login'])) {
    header("location: dashboard.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Cek Admin
    $cek_adm = mysqli_query($koneksi, "SELECT * FROM admin WHERE username = '$username'");

    // Cek Mahasiswa
    $cek_mhs = mysqli_query($koneksi, "SELECT * FROM mahasiswa WHERE nim = '$username'");
    
    // Cek Dosen
    $cek_dsn = mysqli_query($koneksi, "SELECT * FROM dosen WHERE nidn = '$username'");

    if (mysqli_num_rows($cek_adm) > 0) {
        $data = mysqli_fetch_assoc($cek_adm);
        // Password sederhana untuk demo (password disimpan apa adanya)
        if ($password === $data['password']) {
            $_SESSION['login'] = true;
            $_SESSION['id'] = $data['username'];
            $_SESSION['role'] = 'admin';
            $_SESSION['nama'] = $data['nama'];
            header("location: dashboard.php");
            exit;
        }
    } elseif (mysqli_num_rows($cek_mhs) > 0) {
        $data = mysqli_fetch_assoc($cek_mhs);
        // cek password terhadap kolom password (jika kosong gunakan nim sebagai fallback)
        if (($password === $data['password']) || ($data['password']=='' && $password === $data['nim'])) {
            $_SESSION['login'] = true;
            $_SESSION['id'] = $data['nim'];
            $_SESSION['role'] = 'mahasiswa';
            $_SESSION['nama'] = $data['nama'];
            header("location: dashboard.php");
            exit;
        }
    } elseif (mysqli_num_rows($cek_dsn) > 0) {
        $data = mysqli_fetch_assoc($cek_dsn);
        if (($password === $data['password']) || ($data['password']=='' && $password === $data['nidn'])) {
            $_SESSION['login'] = true;
            $_SESSION['id'] = $data['nidn'];
            $_SESSION['role'] = 'dosen';
            $_SESSION['nama'] = $data['nama'];
            header("location: dashboard.php");
            exit;
        }
    } else {
        $error = "NIM/NIDN/NIP/Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Prodi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card p-4 shadow" style="width: 350px;">
        <h4 class="text-center mb-4">📬 Login ke eOffice IT</h4>
        <?php if(!empty($_GET['registered'])): ?>
            <div class="alert alert-success">Pendaftaran berhasil, silakan login.</div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>NIM / NIDN / NIP</label>
                <input type="text" name="username" class="form-control" required placeholder="Masukkan ID">
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Masukkan Password">
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Masuk</button>
        </form>
        <p class="text-center mt-3">
            Belum memiliki akun? <a href="register.php">Daftar di sini</a>
        </p>
        <hr>
        <p class="text-center text-muted small">
            <strong>Copyright eOffice IT © 2026 Prodi Teknik Informatika. All rights reserved.</strong><br>
            Any questions? Contact the administrator.<br>
            WhatsApp <a href="https://wa.me/message/LBJLRUKGKSYVM1" target="_blank">+62 853‑5606‑2877</a><br>
            or Instagram <a href="https://www.instagram.com/himanikka_uisb" target="_blank">@himanikka_uisb</a>
        </p>
    </div>
</body>
</html>