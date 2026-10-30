<?php

namespace App\Controllers;

class InstansiController extends BaseController
{
    public function index()
    {
        return view('admin/instansi/index');
    }

    public function create()
    {
        return view('admin/instansi/create');
    }
}