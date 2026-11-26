<?php

namespace App\Controllers;

use App\Models\GeofenceData;
use Throwable;

class DashboardController
{
    public function index(string $geofenceEndpoint, string $monitoringEndpoint): void
    {
        try {
            $geofenceData = new GeofenceData();
            $instansiList = $geofenceData->activeInstances();
            $selectedInstansi = $instansiList === []
                ? null
                : $geofenceData->activeInstanceDetails((int) $instansiList[0]['id']);
        } catch (Throwable $exception) {
            error_log('Admin dashboard geofence error: ' . $exception->getMessage());
            http_response_code(500);
            exit('Data pemetaan geofencing tidak dapat dimuat.');
        }

        $extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
        require dirname(__DIR__) . '/Views/admin/dashboard.php';
    }

    public function monitoringData(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $token = $_SESSION['admin_token'] ?? null;
        if (!is_string($token) || $token === '') {
            $this->respond(['success' => false, 'message' => 'Sesi Admin tidak ditemukan.'], 401);
        }

        try {
            $result = (new \AdminApiClient())->dashboardMonitoring($token);
        } catch (Throwable $exception) {
            error_log('Admin dashboard monitoring API error: ' . $exception->getMessage());
            $this->respond([
                'success' => false,
                'message' => 'Data monitoring tidak dapat dimuat dari server.',
            ], 502);
        }

        $status = (int) ($result['status'] ?? 0);
        $payload = is_array($result['body'] ?? null) ? $result['body'] : [];
        if ($status !== 200 || ($payload['success'] ?? false) !== true || !is_array($payload['data'] ?? null)) {
            $this->respond([
                'success' => false,
                'message' => in_array($status, [401, 403], true)
                    ? 'Sesi Admin berakhir atau tidak memiliki akses.'
                    : 'Data monitoring tidak dapat dimuat dari server.',
            ], in_array($status, [401, 403], true) ? $status : 502);
        }

        $this->respond($payload, 200);
    }

    public function instanceDetails(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            $this->respond(['error' => 'ID instansi tidak valid.'], 400);
        }

        try {
            $instance = (new GeofenceData())->activeInstanceDetails((int) $id);
        } catch (Throwable $exception) {
            error_log('Admin dashboard geofence detail error: ' . $exception->getMessage());
            $this->respond(['error' => 'Data instansi tidak dapat dimuat.'], 500);
        }

        if ($instance === null) {
            $this->respond(['error' => 'Instansi aktif tidak ditemukan.'], 404);
        }

        $this->respond(['data' => $instance], 200);
    }

    private function respond(array $payload, int $status): never
    {
        http_response_code($status);
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        exit;
    }
}
