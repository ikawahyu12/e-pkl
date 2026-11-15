<?php

namespace App\Models;

use RuntimeException;

class GeofenceData
{
    private \mysqli $connection;

    public function __construct()
    {
        require dirname(__DIR__, 3) . '/config/koneksi.php';
        $this->connection = $koneksi;
    }

    public function activeInstances(): array
    {
        $statement = mysqli_prepare(
            $this->connection,
            "SELECT id, nama FROM instansi WHERE status = 'aktif' ORDER BY nama ASC"
        );

        if ($statement === false || !mysqli_stmt_execute($statement)) {
            $message = $statement === false
                ? mysqli_error($this->connection)
                : mysqli_stmt_error($statement);
            throw new RuntimeException('Gagal memuat instansi aktif: ' . $message);
        }

        $result = mysqli_stmt_get_result($statement);
        if ($result === false) {
            mysqli_stmt_close($statement);
            throw new RuntimeException('Hasil daftar instansi aktif tidak dapat dibaca.');
        }

        $instances = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($statement);

        return $instances;
    }

    public function activeInstanceDetails(int $id): ?array
    {
        $statement = mysqli_prepare(
            $this->connection,
            "SELECT id, nama, jenis, alamat, kota, provinsi, latitude, longitude, radius_geofence_meter
             FROM instansi
             WHERE id = ? AND status = 'aktif'
             LIMIT 1"
        );

        if ($statement === false) {
            throw new RuntimeException('Gagal menyiapkan detail instansi: ' . mysqli_error($this->connection));
        }

        mysqli_stmt_bind_param($statement, 'i', $id);
        if (!mysqli_stmt_execute($statement)) {
            $message = mysqli_stmt_error($statement);
            mysqli_stmt_close($statement);
            throw new RuntimeException('Gagal memuat detail instansi: ' . $message);
        }

        $result = mysqli_stmt_get_result($statement);
        if ($result === false) {
            mysqli_stmt_close($statement);
            throw new RuntimeException('Hasil detail instansi tidak dapat dibaca.');
        }

        $instance = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($statement);

        if ($instance === null) {
            return null;
        }

        $instance['alamat_lengkap'] = implode(', ', array_values(array_filter(
            [$instance['alamat'], $instance['kota'], $instance['provinsi']],
            static fn ($value): bool => is_string($value) && trim($value) !== ''
        )));

        $locations = $this->latestStudentLocations($id);
        $instance['student_location_available'] = count($locations) > 0;
        $instance['inside_count'] = null;
        $instance['outside_count'] = null;

        $latitude = $instance['latitude'];
        $longitude = $instance['longitude'];
        $radius = $instance['radius_geofence_meter'];

        if (
            !is_numeric($latitude)
            || !is_numeric($longitude)
            || (float) $latitude < -90
            || (float) $latitude > 90
            || (float) $longitude < -180
            || (float) $longitude > 180
            || !is_numeric($radius)
            || (float) $radius <= 0
        ) {
            return $instance;
        }

        $insideCount = 0;
        $outsideCount = 0;

        foreach ($locations as $location) {
            $studentLatitude = (float) $location['latitude'];
            $studentLongitude = (float) $location['longitude'];

            if (
                $studentLatitude < -90
                || $studentLatitude > 90
                || $studentLongitude < -180
                || $studentLongitude > 180
            ) {
                continue;
            }

            $distance = $this->distanceInMeters(
                (float) $latitude,
                (float) $longitude,
                $studentLatitude,
                $studentLongitude
            );

            if ($distance <= (float) $radius) {
                $insideCount++;
            } else {
                $outsideCount++;
            }
        }

        $instance['inside_count'] = $insideCount;
        $instance['outside_count'] = $outsideCount;

        return $instance;
    }

    private function latestStudentLocations(int $instanceId): array
    {
        $sql = "
            SELECT latest.siswa_id, latest.latitude, latest.longitude
            FROM (
                SELECT
                    positions.siswa_id,
                    positions.latitude,
                    positions.longitude,
                    ROW_NUMBER() OVER (
                        PARTITION BY positions.siswa_id
                        ORDER BY positions.recorded_at DESC, positions.presensi_id DESC, positions.position_order DESC
                    ) AS position_rank
                FROM (
                    SELECT
                        pp.siswa_id,
                        p.id AS presensi_id,
                        p.latitude_masuk AS latitude,
                        p.longitude_masuk AS longitude,
                        COALESCE(p.waktu_masuk, p.created_at) AS recorded_at,
                        0 AS position_order
                    FROM presensi p
                    INNER JOIN penempatan_pkl pp ON pp.id = p.penempatan_id
                    WHERE pp.instansi_id = ?
                        AND pp.status = 'aktif'
                        AND p.latitude_masuk IS NOT NULL
                        AND p.longitude_masuk IS NOT NULL

                    UNION ALL

                    SELECT
                        pp.siswa_id,
                        p.id AS presensi_id,
                        p.latitude_pulang AS latitude,
                        p.longitude_pulang AS longitude,
                        COALESCE(p.waktu_pulang, p.updated_at) AS recorded_at,
                        1 AS position_order
                    FROM presensi p
                    INNER JOIN penempatan_pkl pp ON pp.id = p.penempatan_id
                    WHERE pp.instansi_id = ?
                        AND pp.status = 'aktif'
                        AND p.latitude_pulang IS NOT NULL
                        AND p.longitude_pulang IS NOT NULL
                ) positions
            ) latest
            WHERE latest.position_rank = 1
        ";

        $statement = mysqli_prepare($this->connection, $sql);
        if ($statement === false) {
            throw new RuntimeException('Gagal menyiapkan data lokasi siswa: ' . mysqli_error($this->connection));
        }

        mysqli_stmt_bind_param($statement, 'ii', $instanceId, $instanceId);
        if (!mysqli_stmt_execute($statement)) {
            $message = mysqli_stmt_error($statement);
            mysqli_stmt_close($statement);
            throw new RuntimeException('Gagal memuat data lokasi siswa: ' . $message);
        }

        $result = mysqli_stmt_get_result($statement);
        if ($result === false) {
            mysqli_stmt_close($statement);
            throw new RuntimeException('Hasil data lokasi siswa tidak dapat dibaca.');
        }

        $locations = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($statement);

        return $locations;
    }

    private function distanceInMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB
    ): float {
        $latitudeDifference = deg2rad($latitudeB - $latitudeA);
        $longitudeDifference = deg2rad($longitudeB - $longitudeA);
        $a = sin($latitudeDifference / 2) ** 2
            + cos(deg2rad($latitudeA))
            * cos(deg2rad($latitudeB))
            * sin($longitudeDifference / 2) ** 2;

        return 2 * 6371000 * asin(min(1, sqrt($a)));
    }
}
