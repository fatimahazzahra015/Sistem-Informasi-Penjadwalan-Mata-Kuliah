<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Jadwal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin User
        User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@utm.ac.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Create Semesters
        $semesterAktif = Semester::create([
            'nama' => 'Genap',
            'tahun_ajaran' => '2025/2026',
            'is_active' => true,
        ]);

        $semesterLama = Semester::create([
            'nama' => 'Ganjil',
            'tahun_ajaran' => '2025/2026',
            'is_active' => false,
        ]);

        // 3. Create Classes
        $kelasNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $kelasModels = [];
        foreach ($kelasNames as $name) {
            $kelasModels[$name] = Kelas::create(['nama_kelas' => $name]);
        }

        // 4. Create Rooms
        $roomsData = [
            ['nama_ruangan' => '407', 'kapasitas' => 40, 'tipe' => 'kelas'],
            ['nama_ruangan' => '406', 'kapasitas' => 40, 'tipe' => 'kelas'],
            ['nama_ruangan' => '306', 'kapasitas' => 40, 'tipe' => 'kelas'],
            ['nama_ruangan' => '304', 'kapasitas' => 40, 'tipe' => 'kelas'],
            ['nama_ruangan' => '203', 'kapasitas' => 40, 'tipe' => 'kelas'],
            ['nama_ruangan' => 'Lab TIA', 'kapasitas' => 30, 'tipe' => 'lab'],
            ['nama_ruangan' => 'LAB CC', 'kapasitas' => 30, 'tipe' => 'lab'],
            ['nama_ruangan' => 'Lab Mul-Net', 'kapasitas' => 30, 'tipe' => 'lab'],
            ['nama_ruangan' => '401', 'kapasitas' => 40, 'tipe' => 'kelas'],
        ];
        $roomModels = [];
        foreach ($roomsData as $room) {
            $roomModels[$room['nama_ruangan']] = Ruangan::create($room);
        }

        // 5. Create Courses (Mata Kuliah)
        $coursesData = [
            ['kode_mk' => 'IF101', 'nama' => 'Organisasi Komputer dan Sistem Operasi', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF102', 'nama' => 'Matematika Diskret', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF103', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF104', 'nama' => 'Metode Statistika', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF105', 'nama' => 'Komputasi Aljabar Linier', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF106', 'nama' => 'Penambangan Teks', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF107', 'nama' => 'Cloud Computing', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF108', 'nama' => 'Dasar Pemrograman Web (front-end)', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF109', 'nama' => 'Interaksi Manusia & Komputer', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF110', 'nama' => 'Informatika Pariwisata', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF111', 'nama' => 'Pengembangan Sistem Berbasis Framework', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF112', 'nama' => 'Internet of Things', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF113', 'nama' => 'Penambangan Data', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF114', 'nama' => 'Pengantar Teknologi Informasi', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF115', 'nama' => 'Basis Data II', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF116', 'nama' => 'Temu-Kembali informasi', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF117', 'nama' => 'Pengenalan Pola', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF118', 'nama' => 'Struktur Data', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF119', 'nama' => 'Kecerdasan Komputasional', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF120', 'nama' => 'Kewarganegaraan', 'sks' => 2, 'semester' => 1],
            ['kode_mk' => 'IF121', 'nama' => 'Agama Islam', 'sks' => 2, 'semester' => 1],
            ['kode_mk' => 'IF122', 'nama' => 'Grafika Komputer', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF123', 'nama' => 'Pemrosesan Sinyal Digital', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF124', 'nama' => 'Keamanan Siber', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF125', 'nama' => 'Realitas Virtual & Augmentasi', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF126', 'nama' => 'Jaringan Komputer II', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF127', 'nama' => 'Sistem Rekomendasi & Personalisasi', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF128', 'nama' => 'Kecerdasan Bisnis', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF129', 'nama' => 'Algoritma Pemrograman', 'sks' => 3, 'semester' => 2],
            ['kode_mk' => 'IF130', 'nama' => 'Biomedika', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF131', 'nama' => 'Pengembangan Aplikasi Terintegrasi', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF132', 'nama' => 'Visi Komputer', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF133', 'nama' => 'Interaksi Manusia Komputer', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF134', 'nama' => 'Pemrosesan Bahasa Alami', 'sks' => 3, 'semester' => 6],
            ['kode_mk' => 'IF135', 'nama' => 'Strategi Algoritma', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF136', 'nama' => 'Penjaminan Mutu Perangkat Lunak', 'sks' => 3, 'semester' => 4],
            ['kode_mk' => 'IF137', 'nama' => 'Metodologi Penelitian', 'sks' => 2, 'semester' => 6],
            ['kode_mk' => 'IF138', 'nama' => 'Pengembangan Aplikasi Web', 'sks' => 3, 'semester' => 4],
        ];
        $courseModels = [];
        foreach ($coursesData as $course) {
            $courseModels[$course['nama']] = MataKuliah::create($course);
        }

        // 6. Create Lecturers (Dosen) and User accounts
        $dosenData = [
            ['nama' => 'Yonathan Ferry Hendrawan, S.T., M.IT', 'kode' => 'Yonathan', 'email' => 'yonathan@utm.ac.id'],
            ['nama' => 'Devie Rosa Anamisa, S.Kom., M.Kom.', 'kode' => 'Devie', 'email' => 'devie@utm.ac.id'],
            ['nama' => 'Fifin Ayu Mufarroha, S.Kom., M.Kom.', 'kode' => 'Fifin', 'email' => 'fifin@utm.ac.id'],
            ['nama' => 'Bain Khusnul Khotimah, ST., M.Kom.', 'kode' => 'Bain', 'email' => 'bain@utm.ac.id'],
            ['nama' => 'Mula’ab, S.Si., M.Kom.', 'kode' => 'Mula’ab', 'email' => 'mul@utm.ac.id'],
            ['nama' => 'Husni, S.Kom., MT', 'kode' => 'Husni', 'email' => 'husni@utm.ac.id'],
            ['nama' => 'Dr. Fika Hastarita Rachman, S.T., M.Eng.', 'kode' => 'Fika', 'email' => 'fika@utm.ac.id'],
            ['nama' => 'Ika Oktavia Suzanti, S.Kom., M.Cs.', 'kode' => 'Suzan', 'email' => 'suzan@utm.ac.id'],
            ['nama' => 'Moch. Kautsar Sophan, S.Kom., M.MT.', 'kode' => 'Ocal', 'email' => 'ocal@utm.ac.id'],
            ['nama' => 'Kurniawan Eka Permana, S.Kom., M.Sc.', 'kode' => 'Kurniawan', 'email' => 'kurniawan@utm.ac.id'],
            ['nama' => 'Hermawan, S.T., M.Kom.', 'kode' => 'Hermawan', 'email' => 'hermawan@utm.ac.id'],
            ['nama' => 'Abdullah Basuki Rahmat, S.Si., M.T.', 'kode' => 'Basuki', 'email' => 'basuki@utm.ac.id'],
            ['nama' => 'Meidya Koeshardianto, S.Si., MT', 'kode' => 'Meidya', 'email' => 'meidya@utm.ac.id'],
            ['nama' => 'Firdaus Solihin, S.Kom., M.Kom.', 'kode' => 'Firdaus', 'email' => 'firdaus@utm.ac.id'],
            ['nama' => 'Ari Kusumaningsih, ST., MT', 'kode' => 'Ari', 'email' => 'ari@utm.ac.id'],
            ['nama' => 'Cucun Very Angkoso, S.T., MT', 'kode' => 'Cucun', 'email' => 'cucun@utm.ac.id'],
            ['nama' => 'Yoga Dwitya Pramudita, S.Kom., M.Cs.', 'kode' => 'Yoga', 'email' => 'yoga@utm.ac.id'],
            ['nama' => 'Arik Kurniawati, S.Kom., MT', 'kode' => 'Arik', 'email' => 'arik@utm.ac.id'],
            ['nama' => 'Dr. Rima Tri Wahyuningrum, ST., MT', 'kode' => 'Rima', 'email' => 'rima@utm.ac.id'],
            ['nama' => 'Achmad Jauhari, S.T., M.Kom.', 'kode' => 'Jauhari', 'email' => 'jauhari@utm.ac.id'],
            ['nama' => 'Dwi Kuswanto, S.Pd., M.T.', 'kode' => 'Dwi', 'email' => 'dwi@utm.ac.id'],
            ['nama' => 'Andharini Dwi Cahyani, S.Kom., M.Kom.', 'kode' => 'Ririn', 'email' => 'ririn@utm.ac.id'],
            ['nama' => 'Sigit Susanto Putro, S.Kom., M.Kom.', 'kode' => 'Sigit', 'email' => 'sigit@utm.ac.id'],
            ['nama' => 'Dr. Noor Ifada, S.T., MISD.', 'kode' => 'Ifada', 'email' => 'ifada@utm.ac.id'],
            ['nama' => 'Prof. Dr. Arif Muntasa, S.Si., M.T.', 'kode' => 'Arif', 'email' => 'arif@utm.ac.id'],
            ['nama' => 'Eka Mala Sari Rochman, S.Kom., M.Kom', 'kode' => 'Eka', 'email' => 'eka@utm.ac.id'],
            ['nama' => 'Iwan Santosa, S.T., MT', 'kode' => 'Iwan', 'email' => 'iwan@utm.ac.id'],
            ['nama' => 'Rika Yunitarini, S.T., MT', 'kode' => 'Rika', 'email' => 'rika@utm.ac.id'],
            ['nama' => 'XXX', 'kode' => 'XXX', 'email' => 'xxx@utm.ac.id'],
        ];
        $dosenModels = [];
        foreach ($dosenData as $d) {
            $user = User::create([
                'name' => $d['nama'],
                'email' => $d['email'],
                'password' => Hash::make('password'),
                'role' => 'dosen',
            ]);

            $dosenModels[$d['kode']] = Dosen::create([
                'user_id' => $user->id,
                'kode_dosen' => $d['kode'],
                'nama' => $d['nama'],
                'program_studi' => 'Teknik Informatika',
            ]);
        }

        // 7. Create Schedules (Jadwal)
        $schedules = [
            // SENIN
            ['407', 'Senin', 1, 3, 'Organisasi Komputer dan Sistem Operasi', 'Yonathan', 'C'],
            ['406', 'Senin', 1, 3, 'Matematika Diskret', 'Devie', 'A'],
            ['306', 'Senin', 1, 3, 'Rekayasa Perangkat Lunak', 'Fifin', 'A'],
            ['304', 'Senin', 1, 3, 'Metode Statistika', 'Bain', 'A'],
            ['Lab TIA', 'Senin', 1, 3, 'Komputasi Aljabar Linier', 'Mula’ab', 'B'],
            ['Lab Mul-Net', 'Senin', 1, 3, 'Penambangan Teks', 'Husni', 'A'],

            ['407', 'Senin', 4, 6, 'Organisasi Komputer dan Sistem Operasi', 'Yonathan', 'D'],
            ['406', 'Senin', 4, 6, 'Matematika Diskret', 'Devie', 'B'],
            ['306', 'Senin', 4, 6, 'Rekayasa Perangkat Lunak', 'Fifin', 'B'],
            ['304', 'Senin', 4, 6, 'Penambangan Data', 'Fika', 'E'],
            ['203', 'Senin', 4, 6, 'Cloud Computing', 'Suzan', 'C'],
            ['Lab TIA', 'Senin', 4, 6, 'Dasar Pemrograman Web (front-end)', 'Ocal', 'C'],
            ['LAB CC', 'Senin', 4, 6, 'Dasar Pemrograman Web (front-end)', 'Kurniawan', 'D'],
            ['Lab Mul-Net', 'Senin', 4, 6, 'Penambangan Teks', 'Husni', 'B'],

            ['407', 'Senin', 8, 10, 'Interaksi Manusia & Komputer', 'Jauhari', 'A'],
            ['406', 'Senin', 8, 10, 'Cloud Computing', 'Dwi', 'A'],
            ['306', 'Senin', 8, 10, 'Rekayasa Perangkat Lunak', 'Ririn', 'C'],
            ['304', 'Senin', 8, 10, 'Metode Statistika', 'Bain', 'B'],
            ['203', 'Senin', 8, 10, 'Informatika Pariwisata', 'Suzan', 'A'],
            ['Lab TIA', 'Senin', 8, 10, 'Dasar Pemrograman Web (front-end)', 'Ocal', 'E'],
            ['LAB CC', 'Senin', 8, 10, 'Dasar Pemrograman Web (front-end)', 'Kurniawan', 'F'],
            ['Lab Mul-Net', 'Senin', 8, 10, 'Pengembangan Sistem Berbasis Framework', 'Hermawan', 'A'],

            ['406', 'Senin', 11, 13, 'Komputasi Aljabar Linier', 'Rima', 'E'],
            ['306', 'Senin', 11, 13, 'Rekayasa Perangkat Lunak', 'Ririn', 'D'],
            ['304', 'Senin', 11, 13, 'Internet of Things', 'Dwi', 'A'],
            ['203', 'Senin', 11, 13, 'Internet of Things', 'Suzan', 'B'],
            ['Lab Mul-Net', 'Senin', 11, 13, 'Pengembangan Sistem Berbasis Framework', 'Hermawan', 'B'],

            // SELASA
            ['407', 'Selasa', 1, 3, 'Pengantar Teknologi Informasi', 'Basuki', 'A'],
            ['406', 'Selasa', 1, 3, 'Basis Data II', 'Meidya', 'A'],
            ['306', 'Selasa', 1, 3, 'Temu-Kembali informasi', 'Firdaus', 'A'],
            ['304', 'Selasa', 1, 3, 'Komputasi Aljabar Linier', 'Ari', 'C'],
            ['203', 'Selasa', 1, 3, 'Komputasi Aljabar Linier', 'Mula’ab', 'A'],
            ['Lab TIA', 'Selasa', 1, 3, 'Pengenalan Pola', 'Cucun', 'A'],
            ['LAB CC', 'Selasa', 1, 3, 'Pengembangan Aplikasi Web', 'Yoga', 'A'],
            ['Lab Mul-Net', 'Selasa', 1, 3, 'Struktur Data', 'Arik', 'E'],

            ['407', 'Selasa', 4, 6, 'Pemrosesan Sinyal Digital', 'Basuki', 'B'],
            ['406', 'Selasa', 4, 6, 'Realitas Virtual & Augmentasi', 'Ari', 'A'],
            ['306', 'Selasa', 4, 6, 'Rekayasa Perangkat Lunak', 'Jauhari', 'E'],
            ['304', 'Selasa', 4, 6, 'Basis Data II', 'Ririn', 'E'],
            ['203', 'Selasa', 4, 6, 'Jaringan Komputer II', 'Suzan', 'A'],
            ['Lab TIA', 'Selasa', 4, 6, 'Grafika Komputer', 'Cucun', 'C'],
            ['LAB CC', 'Selasa', 4, 6, 'Kecerdasan Komputasional', 'Eka', 'B'],
            ['Lab Mul-Net', 'Selasa', 4, 6, 'Struktur Data', 'Rima', 'F'],
            ['401', 'Selasa', 4, 6, 'Pemrosesan Bahasa Alami', 'Fika', 'B'],

            ['407', 'Selasa', 8, 10, 'Keamanan Siber', 'Dwi', 'A'],
            ['406', 'Selasa', 8, 10, 'Cloud Computing', 'Yoga', 'B'],
            ['306', 'Selasa', 8, 10, 'Metode Statistika', 'Sigit', 'D'],
            ['304', 'Selasa', 8, 10, 'Basis Data II', 'Ririn', 'D'],
            ['203', 'Selasa', 8, 10, 'Jaringan Komputer II', 'Suzan', 'B'],
            ['Lab TIA', 'Selasa', 8, 10, 'Sistem Rekomendasi & Personalisasi', 'Ifada', 'A'],
            ['LAB CC', 'Selasa', 8, 10, 'Kecerdasan Komputasional', 'Meidya', 'C'],
            ['Lab Mul-Net', 'Selasa', 8, 10, 'Kecerdasan Komputasional', 'Eka', 'D'],

            ['407', 'Selasa', 11, 13, 'Internet of Things', 'Dwi', 'C'],
            ['306', 'Selasa', 11, 13, 'Komputasi Aljabar Linier', 'Ari', 'F'],
            ['304', 'Selasa', 11, 13, 'Matematika Diskret', 'Suzan', 'E'],
            ['203', 'Selasa', 11, 12, 'Metodologi Penelitian', 'Fika', 'A'],
            ['Lab TIA', 'Selasa', 11, 13, 'Temu-Kembali informasi', 'Firdaus', 'B'],

            // RABU
            ['407', 'Rabu', 1, 3, 'Strategi Algoritma', 'Sigit', 'A'],
            ['406', 'Rabu', 1, 3, 'Matematika Diskret', 'Rika', 'C'],
            ['306', 'Rabu', 1, 3, 'Pengenalan Pola', 'Fika', 'B'],
            ['304', 'Rabu', 1, 3, 'Komputasi Aljabar Linier', 'Ari', 'D'],
            ['203', 'Rabu', 1, 3, 'Penjaminan Mutu Perangkat Lunak', 'Fifin', 'A'],
            ['Lab TIA', 'Rabu', 1, 3, 'Kecerdasan Bisnis', 'Eka', 'B'],
            ['LAB CC', 'Rabu', 1, 3, 'Kecerdasan Bisnis', 'Bain', 'A'],

            ['407', 'Rabu', 4, 6, 'Strategi Algoritma', 'Sigit', 'B'],
            ['406', 'Rabu', 4, 6, 'Matematika Diskret', 'Rika', 'D'],
            ['306', 'Rabu', 4, 6, 'Metode Statistika', 'Basuki', 'E'],
            ['304', 'Rabu', 4, 6, 'Basis Data II', 'Arif', 'B'],
            ['203', 'Rabu', 4, 6, 'Informatika Pariwisata', 'Fifin', 'B'],
            ['Lab TIA', 'Rabu', 4, 6, 'Algoritma Pemrograman', 'Husni', 'A'],
            ['LAB CC', 'Rabu', 4, 6, 'Dasar Pemrograman Web (front-end)', 'Devie', 'G'],
            ['Lab Mul-Net', 'Rabu', 4, 6, 'Kecerdasan Komputasional', 'Eka', 'E'],

            // KAMIS
            ['407', 'Kamis', 1, 3, 'Grafika Komputer', 'Yonathan', 'A'],
            ['406', 'Kamis', 1, 3, 'Penambangan Data', 'Mula’ab', 'A'],
            ['306', 'Kamis', 1, 3, 'Basis Data II', 'Kurniawan', 'C'],
            ['304', 'Kamis', 1, 3, 'Pengembangan Aplikasi Terintegrasi', 'Ocal', 'B'],
            ['203', 'Kamis', 1, 3, 'Penjaminan Mutu Perangkat Lunak', 'Rika', 'B'],
            ['Lab TIA', 'Kamis', 1, 3, 'Struktur Data', 'Hermawan', 'G'],
            ['LAB CC', 'Kamis', 1, 3, 'Pemrosesan Bahasa Alami', 'Fika', 'A'],
            ['Lab Mul-Net', 'Kamis', 1, 3, 'Struktur Data', 'Rima', 'C'],

            ['407', 'Kamis', 4, 6, 'Visi Komputer', 'Cucun', 'B'],
            ['406', 'Kamis', 4, 6, 'Penambangan Data', 'Mula’ab', 'B'],
            ['306', 'Kamis', 4, 6, 'Biomedika', 'Arif', 'A'],
            ['304', 'Kamis', 4, 6, 'Penambangan Data', 'Yoga', 'C'],
            ['203', 'Kamis', 4, 6, 'Interaksi Manusia Komputer', 'Ari', 'B'],
            ['Lab TIA', 'Kamis', 4, 6, 'Struktur Data', 'Meidya', 'A'],
            ['LAB CC', 'Kamis', 4, 6, 'Dasar Pemrograman Web (front-end)', 'Ifada', 'A'],
            ['Lab Mul-Net', 'Kamis', 4, 6, 'Struktur Data', 'Arik', 'D'],

            ['407', 'Kamis', 8, 10, 'Organisasi Komputer dan Sistem Operasi', 'Iwan', 'A'],
            ['406', 'Kamis', 8, 10, 'Pengembangan Aplikasi Terintegrasi', 'Ocal', 'A'],
            ['306', 'Kamis', 8, 10, 'Biomedika', 'Arif', 'B'],
            ['304', 'Kamis', 8, 10, 'Metode Statistika', 'Basuki', 'C'],
            ['203', 'Kamis', 8, 10, 'Penambangan Data', 'Yoga', 'D'],
            ['Lab TIA', 'Kamis', 8, 10, 'Struktur Data', 'Fika', 'B'],
            ['LAB CC', 'Kamis', 8, 10, 'Dasar Pemrograman Web (front-end)', 'Ifada', 'B'],
            ['Lab Mul-Net', 'Kamis', 8, 10, 'Kecerdasan Komputasional', 'Arik', 'A'],

            ['407', 'Kamis', 11, 13, 'Organisasi Komputer dan Sistem Operasi', 'Iwan', 'B'],

            // JUMAT
            ['407', 'Jumat', 1, 2, 'Kewarganegaraan', 'XXX', 'A'],
            ['406', 'Jumat', 1, 3, 'Grafika Komputer', 'Yonathan', 'B'],
            ['407', 'Jumat', 3, 4, 'Agama Islam', 'XXX', 'A'],
            ['406', 'Jumat', 8, 9, 'Kewarganegaraan', 'XXX', 'B'],
        ];

        foreach ($schedules as $s) {
            $room = $roomModels[$s[0]] ?? null;
            $day = $s[1];
            $slotMulai = $s[2];
            $slotSelesai = $s[3];
            $mk = $courseModels[$s[4]] ?? null;
            $dosen = $dosenModels[$s[5]] ?? null;
            $kelas = $kelasModels[$s[6]] ?? null;

            if ($room && $mk && $dosen && $kelas) {
                Jadwal::create([
                    'semester_id' => $semesterAktif->id,
                    'mata_kuliah_id' => $mk->id,
                    'kelas_id' => $kelas->id,
                    'dosen_id' => $dosen->id,
                    'ruangan_id' => $room->id,
                    'hari' => $day,
                    'slot_mulai' => $slotMulai,
                    'slot_selesai' => $slotSelesai,
                ]);
            }
        }
    }
}