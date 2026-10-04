<?php

namespace App\Controllers;

class GuruController
{
    public function index()
    {
        $title = 'Data Guru';
        $activePage = 'data-guru';
        $guru = []; // Data dummy / dari database

        require dirname(__DIR__) . '/Views/admin/guru/index.php';
    }

    public function create()
    {
        $title = 'Tambah Guru';
        $activePage = 'data-guru';

        require dirname(__DIR__) . '/Views/admin/guru/create.php';
    }

    public function detail($id)
    {
        $title = 'Detail Guru';
        $activePage = 'data-guru';

        require dirname(__DIR__) . '/Views/admin/guru/detail.php';
    }

    public function edit($id)
    {
        $title = 'Edit Guru';
        $activePage = 'data-guru';

        require dirname(__DIR__) . '/Views/admin/guru/edit.php';
    }

    public function destroy($id)
    {
        // Proses hapus data
        header('Location: ?page=data-guru');
        exit;
    }
}