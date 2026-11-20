<?php

use PHPUnit\Framework\TestCase;

final class ManajemenPklViewsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/admin-web/public/index.php';
        $_GET = [];
    }

    public function test_placement_list_filters_use_searchable_names_instead_of_database_ids(): void
    {
        $_GET = [
            'search' => 'NIS-10001',
            'periode_search' => 'Ganjil',
            'instansi_search' => 'Mitra',
            'guru_search' => 'Guru Uji',
        ];

        $html = $this->render('index.php', [
            'placements' => [],
            'placementPagination' => [],
            'placementReferences' => [],
        ]);

        self::assertStringContainsString('Nama Siswa / NISN / NIS', $html);
        self::assertStringContainsString('name="periode_search" maxlength="100" value="Ganjil"', $html);
        self::assertStringContainsString('name="instansi_search" maxlength="100" value="Mitra"', $html);
        self::assertStringContainsString('name="guru_search" maxlength="100" value="Guru Uji"', $html);
        self::assertStringContainsString('Guru Pembimbing (nama/NIP)', $html);
        self::assertStringNotContainsString('name="periode_id"', $html);
    }

    public function test_open_placement_detail_exposes_audited_date_and_pic_edit_form(): void
    {
        $html = $this->render('detail.php', [
            'placement' => [
                'id' => 7,
                'status' => 'aktif',
                'siswa' => ['nama_lengkap' => 'Siswa Uji', 'nisn' => '10001'],
                'periode' => [
                    'id' => 3,
                    'nama' => 'PKL Ganjil',
                    'status' => 'berjalan',
                    'tanggal_mulai' => '2026-10-01',
                    'tanggal_selesai' => '2026-12-31',
                ],
                'instansi' => ['id' => 5, 'nama' => 'Mitra Uji'],
                'guru' => ['nama_lengkap' => 'Guru Uji'],
                'pic' => ['id' => 9, 'nama' => 'PIC Uji'],
                'tanggal_mulai' => '2026-10-01',
                'tanggal_selesai' => '2026-12-31',
                'riwayat_status' => [],
                'riwayat_guru' => [],
                'riwayat_perubahan' => [],
            ],
            'placementReferences' => [
                'pic_instansi' => [['id' => 9, 'nama' => 'PIC Uji', 'jabatan' => 'HR']],
            ],
            'placementReferencesLoaded' => true,
            'placementInput' => [],
            'csrfToken' => 'test-token',
        ]);

        self::assertStringContainsString('value="placement-update"', $html);
        self::assertStringContainsString('name="tanggal_mulai"', $html);
        self::assertStringContainsString('name="tanggal_selesai"', $html);
        self::assertStringContainsString('name="pic_instansi_id"', $html);
        self::assertStringContainsString('Riwayat Perubahan Data', $html);
    }

    private function render(string $view, array $variables): string
    {
        $viewPath = dirname(__DIR__) . '/app/Views/admin/manajemen_pkl/' . $view;
        extract($variables, EXTR_SKIP);
        $adminPageError = null;
        $adminPageSuccess = null;
        $adminFlash = null;
        $adminUserName = 'Admin';
        $adminUserRole = 'Administrator';
        $academicYearLabel = '2026/2027';
        $routerUrl = '/admin-web/public/index.php';
        $csrfToken = $variables['csrfToken'] ?? 'test-token';

        ob_start();
        require $viewPath;

        return (string) ob_get_clean();
    }
}
