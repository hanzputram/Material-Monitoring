<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectWorker;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class WorkerSeeder extends Seeder
{
    public function run(): void
    {
        $workers = [
            // Mandor
            [
                'code' => 'WKR-MDR-001',
                'name' => 'Bambang Sutrisno',
                'trade' => 'Mandor',
                'phone' => '0812-3456-7890',
                'nik' => '3302011508800001',
                'daily_rate' => 220000,
                'address' => 'Banyumas, Jawa Tengah',
                'notes' => 'Pengalaman struktur beton gedung bertingkat 15 tahun, sertifikat K3 Konstruksi',
                'is_active' => true,
            ],
            [
                'code' => 'WKR-MDR-002',
                'name' => 'Wahyudi Santoso',
                'trade' => 'Mandor',
                'phone' => '0813-9876-5432',
                'nik' => '3302012005820002',
                'daily_rate' => 210000,
                'address' => 'Kebumen, Jawa Tengah',
                'notes' => 'Mandor finishing, arsitektural, dan lanskap',
                'is_active' => true,
            ],

            // Tukang Besi / Pembesian
            [
                'code' => 'WKR-TKG-001',
                'name' => 'Joko Purwanto',
                'trade' => 'Tukang Besi / Pembesian',
                'phone' => '0857-1122-3344',
                'nik' => '3302021203850003',
                'daily_rate' => 175000,
                'address' => 'Purwokerto, Jawa Tengah',
                'notes' => 'Ahli perakitan tulangan pondasi bore pile, sloof, dan kolom D16-D25',
                'is_active' => true,
            ],
            [
                'code' => 'WKR-TKG-002',
                'name' => 'Slamet Riyadi',
                'trade' => 'Tukang Besi / Pembesian',
                'phone' => '0858-2233-4455',
                'nik' => '3302022507870004',
                'daily_rate' => 170000,
                'address' => 'Cilacap, Jawa Tengah',
                'notes' => 'Spesialis pembesian balok dan plat lantai',
                'is_active' => true,
            ],

            // Tukang Kayu / Bekisting
            [
                'code' => 'WKR-TKG-003',
                'name' => 'Sugeng Supriyadi',
                'trade' => 'Tukang Kayu / Bekisting',
                'phone' => '0821-3344-5566',
                'nik' => '3302031006840005',
                'daily_rate' => 170000,
                'address' => 'Wonosobo, Jawa Tengah',
                'notes' => 'Spesialis fabrikasi bekisting kolom, balok, dan scaffolding',
                'is_active' => true,
            ],
            [
                'code' => 'WKR-TKG-004',
                'name' => 'Agus Priyono',
                'trade' => 'Tukang Kayu / Bekisting',
                'phone' => '0822-4455-6677',
                'nik' => '3302031809860006',
                'daily_rate' => 165000,
                'address' => 'Purbalingga, Jawa Tengah',
                'notes' => 'Pemasangan bekisting plat lantai dan tangga',
                'is_active' => true,
            ],

            // Tukang Batu & Plesteran
            [
                'code' => 'WKR-TKG-005',
                'name' => 'Darmanto',
                'trade' => 'Tukang Batu',
                'phone' => '0877-5566-7788',
                'nik' => '3302040401880007',
                'daily_rate' => 165000,
                'address' => 'Brebes, Jawa Tengah',
                'notes' => 'Pasangan bata merah, hebel presisi, plesteran, dan acian halus',
                'is_active' => true,
            ],
            [
                'code' => 'WKR-TKG-006',
                'name' => 'Suparman',
                'trade' => 'Tukang Batu',
                'phone' => '0878-6677-8899',
                'nik' => '3302041911890008',
                'daily_rate' => 160000,
                'address' => 'Tegal, Jawa Tengah',
                'notes' => 'Pasangan pondasi batu kali dan saluran drainase',
                'is_active' => true,
            ],

            // Tukang Listrik & ME
            [
                'code' => 'WKR-TKG-007',
                'name' => 'Rahmat Hidayat',
                'trade' => 'Tukang Listrik / ME',
                'phone' => '0812-7788-9900',
                'nik' => '3302052204900009',
                'daily_rate' => 190000,
                'address' => 'Semarang, Jawa Tengah',
                'notes' => 'Instalasi pipa konduit, panel daya, genset, dan grounding penangkal petir',
                'is_active' => true,
            ],

            // Tukang Cat & Finishing
            [
                'code' => 'WKR-TKG-008',
                'name' => 'Eko Prasetyo',
                'trade' => 'Tukang Cat',
                'phone' => '0813-8899-0011',
                'nik' => '3302061408910010',
                'daily_rate' => 160000,
                'address' => 'Magelang, Jawa Tengah',
                'notes' => 'Pengecatan dinding eksterior weathershield dan plafon interior',
                'is_active' => true,
            ],

            // Tukang Las
            [
                'code' => 'WKR-TKG-009',
                'name' => 'Hendro Siswanto',
                'trade' => 'Tukang Las',
                'phone' => '0856-9900-1122',
                'nik' => '3302070703870011',
                'daily_rate' => 185000,
                'address' => 'Surakarta, Jawa Tengah',
                'notes' => 'Sertifikat las 3G/4G, pengelasan konstruksi baja WF dan kanopi',
                'is_active' => true,
            ],

            // Pekerja / Kenek / Helper
            [
                'code' => 'WKR-KNK-001',
                'name' => 'Nur Kholis',
                'trade' => 'Pekerja / Kenek / Helper',
                'phone' => '0857-0011-2233',
                'nik' => '3302081509930012',
                'daily_rate' => 130000,
                'address' => 'Banyumas, Jawa Tengah',
                'notes' => 'Helper molen cor, angkut material, pembersihan lokasi',
                'is_active' => true,
            ],
            [
                'code' => 'WKR-KNK-002',
                'name' => 'Triyono',
                'trade' => 'Pekerja / Kenek / Helper',
                'phone' => '0858-1122-3344',
                'nik' => '3302082810940013',
                'daily_rate' => 130000,
                'address' => 'Cilacap, Jawa Tengah',
                'notes' => 'Helper pembesian dan penarikan kawat bendrat',
                'is_active' => true,
            ],
            [
                'code' => 'WKR-KNK-003',
                'name' => 'Arif Munandar',
                'trade' => 'Pekerja / Kenek / Helper',
                'phone' => '0859-2233-4455',
                'nik' => '3302080302950014',
                'daily_rate' => 130000,
                'address' => 'Kebumen, Jawa Tengah',
                'notes' => 'Helper bekisting dan pembongkaran scaffolding',
                'is_active' => true,
            ],
        ];

        foreach ($workers as $w) {
            Worker::updateOrCreate(
                ['code' => $w['code']],
                $w
            );
        }

        // Alokasikan beberapa pekerja ke project pertama yang ada jika tersedia
        $project = Project::first();
        $admin = User::first();

        if ($project) {
            $mandor = Worker::where('code', 'WKR-MDR-001')->first();
            $tkgBesi1 = Worker::where('code', 'WKR-TKG-001')->first();
            $tkgBesi2 = Worker::where('code', 'WKR-TKG-002')->first();
            $tkgKayu = Worker::where('code', 'WKR-TKG-003')->first();
            $tkgBatu = Worker::where('code', 'WKR-TKG-005')->first();
            $tkgListrik = Worker::where('code', 'WKR-TKG-007')->first();
            $kenek1 = Worker::where('code', 'WKR-KNK-001')->first();
            $kenek2 = Worker::where('code', 'WKR-KNK-002')->first();
            $kenek3 = Worker::where('code', 'WKR-KNK-003')->first();

            $allocations = [
                ['worker' => $mandor, 'status' => 'active', 'notes' => 'Penanggung jawab lapangan zona struktur Lantai 1-3'],
                ['worker' => $tkgBesi1, 'status' => 'active', 'notes' => 'Perakitan tulangan kolom utama & balok ring'],
                ['worker' => $tkgBesi2, 'status' => 'active', 'notes' => 'Pembesian plat lantai 2'],
                ['worker' => $tkgKayu, 'status' => 'active', 'notes' => 'Penyetelan bekisting balok & perancah lantai 2'],
                ['worker' => $tkgBatu, 'status' => 'active', 'notes' => 'Pasangan dinding hebel lantai dasar'],
                ['worker' => $tkgListrik, 'status' => 'standby', 'notes' => 'Standby instalasi konduit pipa ME pengecoran'],
                ['worker' => $kenek1, 'status' => 'active', 'notes' => 'Operator molen & distribusi adukan cor'],
                ['worker' => $kenek2, 'status' => 'active', 'notes' => 'Pembantu pembesian & pemotongan besi rebar'],
                ['worker' => $kenek3, 'status' => 'standby', 'notes' => 'Cadangan penanganan material tiba'],
            ];

            foreach ($allocations as $alloc) {
                if ($alloc['worker']) {
                    ProjectWorker::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'worker_id' => $alloc['worker']->id,
                        ],
                        [
                            'assigned_trade' => $alloc['worker']->trade,
                            'daily_wage' => $alloc['worker']->daily_rate,
                            'status' => $alloc['status'],
                            'start_date' => now()->subDays(10)->toDateString(),
                            'notes' => $alloc['notes'],
                            'added_by' => $admin?->id,
                        ]
                    );
                }
            }
        }
    }
}
