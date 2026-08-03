<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Ruangan;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Jadwal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $semester;
    protected $course1;
    protected $course2;
    protected $room;
    protected $kelas;
    protected $lecturer1;
    protected $lecturer2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Admin
        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        // 2. Create active semester
        $this->semester = Semester::create([
            'nama' => 'Genap',
            'tahun_ajaran' => '2023/2024',
            'is_active' => true
        ]);

        // 3. Create master data
        $this->course1 = MataKuliah::create([
            'kode_mk' => 'MK01',
            'nama' => 'Matematika Diskret',
            'sks' => 3
        ]);

        $this->course2 = MataKuliah::create([
            'kode_mk' => 'MK02',
            'nama' => 'Organisasi Komputer',
            'sks' => 3
        ]);

        $this->room = Ruangan::create([
            'nama_ruangan' => '407',
            'kapasitas' => 40,
            'tipe' => 'kelas'
        ]);

        $this->kelas = Kelas::create([
            'nama_kelas' => 'A'
        ]);

        // Create lecturers
        $userLecturer1 = User::create([
            'name' => 'Dosen A',
            'email' => 'dosena@test.com',
            'password' => bcrypt('password'),
            'role' => 'dosen'
        ]);
        $this->lecturer1 = Dosen::create([
            'user_id' => $userLecturer1->id,
            'kode_dosen' => 'DA',
            'nama' => 'Dosen A'
        ]);

        $userLecturer2 = User::create([
            'name' => 'Dosen B',
            'email' => 'dosenb@test.com',
            'password' => bcrypt('password'),
            'role' => 'dosen'
        ]);
        $this->lecturer2 = Dosen::create([
            'user_id' => $userLecturer2->id,
            'kode_dosen' => 'DB',
            'nama' => 'Dosen B'
        ]);
    }

    /** @test */
    public function test_admin_can_schedule_class_without_conflicts()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.jadwal.store'), [
                'mata_kuliah_id' => $this->course1->id,
                'kelas_id' => $this->kelas->id,
                'dosen_id' => $this->lecturer1->id,
                'ruangan_id' => $this->room->id,
                'hari' => 'Senin',
                'slot_mulai' => 1,
                'slot_selesai' => 3,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('jadwal', 1);
    }

    /** @test */
    public function test_system_detects_room_conflicts()
    {
        // 1. Create a valid schedule
        Jadwal::create([
            'semester_id' => $this->semester->id,
            'mata_kuliah_id' => $this->course1->id,
            'kelas_id' => $this->kelas->id,
            'dosen_id' => $this->lecturer1->id,
            'ruangan_id' => $this->room->id,
            'hari' => 'Senin',
            'slot_mulai' => 1,
            'slot_selesai' => 3,
        ]);

        // 2. Try to schedule another class in the SAME ROOM at overlapping slots
        $response = $this->actingAs($this->admin)
            ->post(route('admin.jadwal.store'), [
                'mata_kuliah_id' => $this->course2->id,
                'kelas_id' => $this->kelas->id,
                'dosen_id' => $this->lecturer2->id, // different lecturer
                'ruangan_id' => $this->room->id, // same room
                'hari' => 'Senin',
                'slot_mulai' => 2, // overlaps with 1-3
                'slot_selesai' => 4,
            ]);

        $response->assertSessionHasErrors(['conflict']);
        $this->assertDatabaseCount('jadwal', 1); // should not be stored
    }

    /** @test */
    public function test_system_detects_lecturer_conflicts()
    {
        // 1. Create a valid schedule
        Jadwal::create([
            'semester_id' => $this->semester->id,
            'mata_kuliah_id' => $this->course1->id,
            'kelas_id' => $this->kelas->id,
            'dosen_id' => $this->lecturer1->id,
            'ruangan_id' => $this->room->id,
            'hari' => 'Senin',
            'slot_mulai' => 1,
            'slot_selesai' => 3,
        ]);

        // Create another room for the second class
        $room2 = Ruangan::create([
            'nama_ruangan' => '406',
            'kapasitas' => 40,
            'tipe' => 'kelas'
        ]);

        // 2. Try to schedule another class with the SAME LECTURER in a different room
        $response = $this->actingAs($this->admin)
            ->post(route('admin.jadwal.store'), [
                'mata_kuliah_id' => $this->course2->id,
                'kelas_id' => $this->kelas->id,
                'dosen_id' => $this->lecturer1->id, // same lecturer
                'ruangan_id' => $room2->id, // different room
                'hari' => 'Senin',
                'slot_mulai' => 2, // overlaps with 1-3
                'slot_selesai' => 4,
            ]);

        $response->assertSessionHasErrors(['conflict']);
        $this->assertDatabaseCount('jadwal', 1);
    }

    /** @test */
    public function test_can_export_full_schedule_pdf()
    {
        $response = $this->get(route('export.pdf.full'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function test_lecturer_can_export_personal_schedule_pdf()
    {
        $response = $this->actingAs($this->lecturer1->user)
            ->get(route('export.pdf.dosen'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function test_student_can_export_personal_krs_pdf()
    {
        $sched = Jadwal::create([
            'semester_id' => $this->semester->id,
            'mata_kuliah_id' => $this->course1->id,
            'kelas_id' => $this->kelas->id,
            'dosen_id' => $this->lecturer1->id,
            'ruangan_id' => $this->room->id,
            'hari' => 'Senin',
            'slot_mulai' => 1,
            'slot_selesai' => 3,
        ]);

        $response = $this->get(route('export.pdf.student') . "?ids={$sched->id}");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
