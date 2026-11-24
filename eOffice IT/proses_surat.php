<?php
session_start();
include 'koneksi.php';

// Proses Kirim Surat
if (isset($_POST['kirim'])) {
    $pengirim = $_SESSION['id'];
    $penerima = trim($_POST['penerima']);
    $perihal = trim($_POST['perihal']);
    $isi = trim($_POST['isi']);
    $tanggal = date('Y-m-d');
    $filePath = null;

    // handle optional pdf upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) === 'pdf') {
            $targetDir = __DIR__ . '/uploads';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $filename = time() . '_' . basename($_FILES['file']['name']);
            $targetPath = $targetDir . '/' . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/' . $filename;
            }
        }
    }

    $query = "INSERT INTO surat (pengirim_id, penerima_id, perihal, isi_surat, tanggal, status";
    if ($filePath) {
        $query .= ", file_path";
    }
    $query .= ") VALUES ('$pengirim', '$penerima', '$perihal', '$isi', '$tanggal', 'menunggu'";
    if ($filePath) {
        $query .= ", '$filePath'";
    }
    $query .= ")";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Surat berhasil dikirim!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim surat: " . mysqli_error($koneksi) . "'); window.location='dashboard.php';</script>";
    }
}

// Proses Kirim Ulang (mahasiswa)
if (isset($_POST['resend'])) {
    $pengirim = $_SESSION['id'];
    $originalId = $_POST['resend_original'];
    // fetch original penerima_id
    $res = mysqli_query($koneksi, "SELECT penerima_id FROM surat WHERE id_surat = '$originalId'");
    $row = mysqli_fetch_assoc($res);
    $penerima = $row ? $row['penerima_id'] : '';
    $perihal = trim($_POST['perihal']);
    $isi = '';
    $tanggal = date('Y-m-d');
    $filePath = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) === 'pdf') {
            $targetDir = __DIR__ . '/uploads';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $filename = time() . '_' . basename($_FILES['file']['name']);
            $targetPath = $targetDir . '/' . $filename;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $filePath = 'uploads/' . $filename;
            }
        }
    }

    $query = "INSERT INTO surat (pengirim_id, penerima_id, perihal, isi_surat, tanggal, status";
    if ($filePath) {
        $query .= ", file_path";
    }
    $query .= ") VALUES ('$pengirim', '$penerima', '$perihal', '$isi', '$tanggal', 'menunggu'";
    if ($filePath) {
        $query .= ", '$filePath'";
    }
    $query .= ")";

    if (mysqli_query($koneksi, $query)) {
        // mark original as having been replied/resend so button disappears
        mysqli_query($koneksi, "UPDATE surat SET resend_done = 1 WHERE id_surat = '$originalId'");
        echo "<script>alert('Surat berhasil dikirim ulang!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim ulang surat: " . mysqli_error($koneksi) . "'); window.location='dashboard.php';</script>";
    }
}

// Proses Update Status Surat
if (isset($_POST['update_status'])) {
    $id = $_POST['id_surat'];
    $status = $_POST['status'];
    $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';
    $tanggal_status = date('Y-m-d');

    if ($status == 'dikembalikan' && empty($catatan)) {
        echo "<script>alert('Alasan pengembalian harus diisi!'); window.location='dashboard.php';</script>";
        exit;
    }

    $query = "UPDATE surat SET status = '$status', catatan_status = '$catatan', tanggal_status = '$tanggal_status' 
              WHERE id_surat = '$id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Status surat berhasil diupdate!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal update status: " . mysqli_error($koneksi) . "'); window.location='dashboard.php';</script>";
    }
}
?>