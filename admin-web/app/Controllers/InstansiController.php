<?php

namespace App\Controllers;

class InstansiController
{
    public function index()
    {
        $title = 'Data Instansi';
        $activePage = 'data-instansi';
        $instansi = []; // Data dummy / dari database

        require dirname(__DIR__) . '/Views/admin/instansi/index.php';
    }

    public function create()
    {
        $title = 'Tambah Instansi';
        $activePage = 'data-instansi';

        require dirname(__DIR__) . '/Views/admin/instansi/create.php';
    }

    public function detail($id)
    {
        $title = 'Detail Instansi';
        $activePage = 'data-instansi';
        $mitra = []; // Data dummy / dari database

        require dirname(__DIR__) . '/Views/admin/instansi/detail.php';
    }

    public function edit($id)
    {
        $title = 'Edit Instansi';
        $activePage = 'data-instansi';
        $mitra = []; // Data dummy / dari database

        require dirname(__DIR__) . '/Views/admin/instansi/edit.php';
    }

    public function destroy($id)
    {
        // Proses hapus data instansi

        header('Location: ?page=data-instansi');
        exit;
    }
}
