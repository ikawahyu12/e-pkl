<?php

namespace App\Controllers;

use App\Models\GeofenceData;
use Throwable;

class DashboardController
{
    public function index(string $geofenceEndpoint): void
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
