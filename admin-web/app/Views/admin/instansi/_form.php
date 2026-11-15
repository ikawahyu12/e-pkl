<!-- Nama Instansi -->
<div class="mb-3">
    <label class="form-label">Nama Instansi / Perusahaan</label>
    <input type="text" name="nama_instansi" class="form-control"
        value="<?= htmlspecialchars($mitra['nama_instansi'] ?? '') ?>" required>
</div>

<!-- Alamat -->
<div class="mb-3">
    <label class="form-label">Alamat Lengkap</label>
    <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($mitra['alamat'] ?? '') ?></textarea>
</div>

<!-- Pembimbing -->
<div class="mb-3">
    <label class="form-label">Penanggung Jawab / Pembimbing</label>
    <input type="text" name="pembimbing_lapangan" class="form-control"
        value="<?= htmlspecialchars($mitra['pembimbing_lapangan'] ?? '') ?>" required>
</div>

<!-- Baris 1: Telepon dan Kuota -->
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">No. Telepon / HP</label>
        <input type="text" name="telepon" class="form-control"
            value="<?= htmlspecialchars($mitra['telepon'] ?? '') ?>" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Kuota Siswa</label>
        <input type="number" name="kuota" class="form-control"
            min="1"
            value="<?= htmlspecialchars($mitra['kuota'] ?? '') ?>" required>
    </div>
</div>

<!-- Baris 2: Latitude dan Longitude -->
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Latitude</label>
        <input type="number" name="latitude" class="form-control"
            step="0.0000001"
            placeholder="Contoh: -7.7543000"
            value="<?= htmlspecialchars($mitra['latitude'] ?? '') ?>" required>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Longitude</label>
        <input type="number" name="longitude" class="form-control"
            step="0.0000001"
            placeholder="Contoh: 111.9025000"
            value="<?= htmlspecialchars($mitra['longitude'] ?? '') ?>" required>
    </div>
</div>

<!-- Baris 3: Radius Geofence -->
<div class="mb-3">
    <label class="form-label">Radius Lokasi (Meter)</label>
    <input type="number" name="radius_geofence_meter" class="form-control"
        min="1"
        step="1"
        placeholder="Contoh: 100"
        value="<?= htmlspecialchars($mitra['radius_geofence_meter'] ?? '') ?>" required>
</div>