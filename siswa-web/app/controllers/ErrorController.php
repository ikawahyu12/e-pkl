<?php
class ErrorController extends Controller
{
    public function notFound()
    {
        http_response_code(404);
        $this->render('errors/404', ['title' => 'Tidak ditemukan', 'showBack' => true]);
    }
}
