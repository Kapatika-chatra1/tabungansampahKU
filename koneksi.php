<?php
$host     = "localhost";   // biasanya 'localhost' di XAMPP/Laragon
$user     = "root";        // default XAMPP user = root
$password = "";            // default XAMPP password = kosong
$dbname   = "tabungansampahku"; // ganti sesuai nama database

// Membuat koneksi
$conn = mysqli_connect($host, $user, $password, $dbname);

