<?php
// Alias route /login -> AuthController::login
require_once APP_PATH . '/controllers/AuthController.php';
class LoginController extends AuthController
{
    public function index() { $this->login(); }
}
