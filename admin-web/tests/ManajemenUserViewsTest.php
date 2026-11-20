<?php

use PHPUnit\Framework\TestCase;

final class ManajemenUserViewsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/admin-web/public/index.php';
        $_GET = [];
    }

    public function test_management_titles_are_shown_once_inside_styled_cards(): void
    {
        $pages = [
            ['admin/index.php', 'Manajemen Admin'],
            ['siswa/index.php', 'Manajemen Siswa'],
            ['guru/index.php', 'Manajemen Guru'],
        ];

        foreach ($pages as [$view, $title]) {
            [$html, $hidePageHeading] = $this->render($view);

            self::assertTrue($hidePageHeading, $title);
            self::assertSame(
                1,
                substr_count(
                    $html,
                    '<h5 class="font-weight-bold text-dark mb-1">' . $title . '</h5>',
                ),
                $title,
            );
            self::assertStringNotContainsString('<header class="page-heading', $html, $title);
            self::assertStringContainsString('.user-card-heading', $html, $title);
            self::assertStringContainsString('user-card-heading d-flex', $html, $title);
        }
    }

    private function render(string $view): array
    {
        $viewPath = dirname(__DIR__) . '/app/Views/admin/manajemen_user/' . $view;
        $adminUsers = [];
        $adminPagination = [];
        $studentUsers = [];
        $studentPagination = [];
        $teacherUsers = [];
        $teacherPagination = [];
        $adminPageError = null;
        $adminPageSuccess = null;
        $adminFlash = null;
        $adminUserName = 'Admin';
        $adminUserRole = 'Administrator';
        $academicYearLabel = '2026/2027';
        $routerUrl = '/admin-web/public/index.php';
        $csrfToken = 'test-token';

        ob_start();
        require $viewPath;

        return [(string) ob_get_clean(), $hidePageHeading];
    }
}
