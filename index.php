<?php
// ========== CONFIG ==========
$dataFile = "data.json";
 
// Kalau file JSON belum ada, bikin kosong default
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode(["jadwal" => [], "tugas" => []], JSON_PRETTY_PRINT));
}

// Load data dari JSON
$data = json_decode(file_get_contents($dataFile), true);

// Kalau gagal decode
if (!is_array($data)) {
    $data = ["jadwal" => [], "tugas" => []];
}

// ========== FUNCTION ==========

// Hitung total SKS
function totalSKS($jadwal) {
    $total = 0;
    foreach ($jadwal as $j) {
        $total += $j["sks"];
    }
    return $total;
}

// Ambil jadwal hari ini
function jadwalHariIni($jadwal) {
    $hariIni = date("l"); // contoh: Monday, Tuesday...
    $result = [];
    foreach ($jadwal as $j) {
        if ($j["hari"] == $hariIni) {
            $result[] = $j;
        }
    }
    return $result;
}

// Cek tugas mendekati deadline (<= 2 hari lagi)
function tugasMendekatiDeadline($tugas) {
    $alerts = [];
    foreach ($tugas as $t) {
        if ($t["status"] == "belum") {
            $sisa = (strtotime($t["deadline"]) - time()) / 86400;
            if ($sisa <= 2 && $sisa >= 0) {
                $alerts[] = $t;
            }
        }
    }
    return $alerts;
}

// ========== HANDLE FORM ==========
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["aksi"]) && $_POST["aksi"] == "tambah_jadwal") {
        $baru = [
            "mata_kuliah" => $_POST["mata_kuliah"],
            "hari" => $_POST["hari"],
            "jam" => $_POST["jam"],
            "ruangan" => $_POST["ruangan"],
            "dosen" => $_POST["dosen"],
            "sks" => (int)$_POST["sks"]
        ];
        $data["jadwal"][] = $baru;
    }
    if (isset($_POST["aksi"]) && $_POST["aksi"] == "tambah_tugas") {
        $baru = [
            "nama" => $_POST["nama"],
            "deadline" => $_POST["deadline"],
            "status" => "belum"
        ];
        $data["tugas"][] = $baru;
    }
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: agenda.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Agenda Mahasiswa</title>
    <style>
        body { 
            font-family: Arial; 
            margin: 20px; 
            background: #e6f2f7; /* biru muda background */
        }
        h1 { 
            color: #004080; /* biru tua judul */
        }
        .card { 
            background: #ffffff; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            border-left: 6px solid #3399cc; /* aksen biru laut */
            box-shadow: 0 2px 6px rgba(0,0,0,.1); 
        }
        h2 { 
            color: #006699; /* judul card biru laut */
        }
        ul { 
            margin: 0; 
            padding: 0; 
            list-style: none; 
        }
        li { 
            padding: 6px 0; 
            border-bottom: 1px solid #cce6f0; 
        }
        li:last-child { 
            border-bottom: none; 
        }
        form { 
            margin-top: 10px; 
        }
        input, button { 
            margin: 5px 0; 
            padding: 8px; 
            border: 1px solid #99ccff; 
            border-radius: 4px; 
        }
        input:focus { 
            outline: none; 
            border-color: #3399cc; 
            box-shadow: 0 0 4px #99ccff; 
        }
        button { 
            background: #3399cc; 
            color: white; 
            border: none; 
            cursor: pointer; 
            transition: 0.2s; 
        }
        button:hover { 
            background: #267ba5; 
        }
    </style>
</head>
<body>
    <h1>🔖 Agenda Mahasiswa</h1>

    <div class="card">
        <h2>Jadwal Hari Ini (<?php echo date("l"); ?>)</h2>
        <ul>
            <?php foreach(jadwalHariIni($data["jadwal"]) as $j): ?>
                <li><?php echo $j["mata_kuliah"]." - ".$j["jam"]." - ".$j["ruangan"]." (".$j["dosen"].")"; ?></li>
            <?php endforeach; ?>
            <?php if (count(jadwalHariIni($data["jadwal"])) == 0) echo "<li>Tidak ada jadwal.</li>"; ?>
        </ul>
    </div>

    <div class="card">
        <h2>Tugas Belum Diselesaikan</h2>
        <ul>
            <?php foreach($data["tugas"] as $t): ?>
                <?php if ($t["status"] == "belum"): ?>
                    <li><?php echo $t["nama"]." (Deadline: ".$t["deadline"].")"; ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (count(array_filter($data["tugas"], fn($x) => $x["status"]=="belum")) == 0) echo "<li>Tidak ada tugas ditambahkan.</li>"; ?>
        </ul>
    </div>

    <div class="card">
        <h2>Total SKS</h2>
        <p><?php echo totalSKS($data["jadwal"]); ?> SKS</p>
    </div>

    <div class="card">
        <h2>⚠️ Alert Deadline Dekat</h2>
        <ul>
            <?php foreach(tugasMendekatiDeadline($data["tugas"]) as $a): ?>
                <li><?php echo $a["nama"]." (Deadline: ".$a["deadline"].")"; ?></li>
            <?php endforeach; ?>
            <?php if (count(tugasMendekatiDeadline($data["tugas"])) == 0) echo "<li>Tidak ada tugas mendesak.</li>"; ?>
        </ul>
    </div>

    <div class="card">
        <h2>➕ Tambah Jadwal Kuliah</h2>
        <form method="post">
            <input type="hidden" name="aksi" value="tambah_jadwal">
            Mata Kuliah: <input type="text" name="mata_kuliah" required><br>
            Hari: <input type="text" name="hari" placeholder="Monday" required><br>
            Jam: <input type="text" name="jam" required><br>
            Ruangan: <input type="text" name="ruangan" required><br>
            Dosen: <input type="text" name="dosen" required><br>
            SKS: <input type="number" name="sks" required><br>
            <button type="submit">Simpan</button>
        </form>
    </div>

    <div class="card">
        <h2>➕ Tambah Tugas</h2>
        <form method="post">
            <input type="hidden" name="aksi" value="tambah_tugas">
            Nama Tugas: <input type="text" name="nama" required><br>
            Deadline: <input type="date" name="deadline" required><br>
            <button type="submit">Simpan</button>
        </form>
    </div>
</body>
</html>
