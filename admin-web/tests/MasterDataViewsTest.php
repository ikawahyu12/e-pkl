<?php

use PHPUnit\Framework\TestCase;

final class MasterDataViewsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/admin-web/public/index.php';
        $_GET = [];
    }

    public function test_instance_form_posts_csrf_protected_api_action_and_escapes_values(): void
    {
        $html = $this->render('instansi.php', [
            'masterMode' => 'create',
            'instance' => ['nama' => '<script>alert(1)</script>', 'alamat' => '', 'status' => 'aktif'],
            'csrfToken' => 'test-csrf-token',
        ]);

        self::assertStringContainsString('name="action" value="master-instance-create"', $html);
        self::assertStringContainsString('name="csrf_token" value="test-csrf-token"', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_instance_detail_exposes_pic_status_and_period_quota_management_without_delete(): void
    {
        $html = $this->render('instansi.php', [
            'masterMode' => 'detail',
            'instance' => [
                'id' => 4,
                'nama' => 'Mitra',
                'alamat' => 'Jalan',
                'status' => 'aktif',
                'pic_instansi' => [['id' => 9, 'nama' => 'PIC', 'aktif' => true]],
                'kuota_periode' => [['kuota_siswa' => null, 'periode' => ['nama' => 'PKL Ganjil']]],
            ],
            'pics' => [['id' => 9, 'nama' => 'PIC', 'aktif' => true]],
            'csrfToken' => 'test-csrf-token',
        ]);

        self::assertStringContainsString('master-instance-status', $html);
        self::assertStringContainsString('master-pic-create', $html);
        self::assertStringContainsString('master-pic-status', $html);
        self::assertStringContainsString('Tanpa batas', $html);
        self::assertStringContainsString('page=kuota-instansi', $html);
        self::assertStringNotContainsString('master-instance-delete', $html);
        self::assertStringNotContainsString('master-pic-delete', $html);
    }

    public function test_year_period_and_quota_views_submit_expected_actions(): void
    {
        $yearHtml = $this->render('tahun_ajaran.php', [
            'masterMode' => 'create',
            'year' => ['aktif' => false],
            'csrfToken' => 'token',
        ]);
        self::assertStringContainsString('master-year-create', $yearHtml);
        self::assertStringContainsString('name="aktif" value="0"', $yearHtml);

        $periodHtml = $this->render('periode_pkl.php', [
            'masterMode' => 'create',
            'period' => ['status' => 'persiapan'],
            'years' => [['id' => 1, 'nama' => '2026/2027']],
            'csrfToken' => 'token',
        ]);
        self::assertStringContainsString('master-period-create', $periodHtml);
        self::assertStringContainsString('name="tahun_ajaran_id"', $periodHtml);

        $quotaHtml = $this->render('kuota.php', [
            'quotas' => [],
            'instances' => [['id' => 2, 'nama' => 'Mitra', 'status' => 'aktif']],
            'periods' => [['id' => 3, 'nama' => 'PKL Ganjil', 'status' => 'persiapan']],
            'csrfToken' => 'token',
        ]);
        self::assertStringContainsString('master-quota-save', $quotaHtml);
        self::assertStringContainsString('name="kuota_siswa"', $quotaHtml);
        self::assertStringContainsString('Kosong = tanpa batas', $quotaHtml);
    }

    private function render(string $view, array $variables): string
    {
        $viewPath = dirname(__DIR__) . '/app/Views/admin/master_data/' . $view;
        extract($variables, EXTR_SKIP);
        $adminPageError = null;
        $adminPageSuccess = null;
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
