@echo off
start "API 8000" cmd /k "cd /d C:\laragon\www\e-pkl\api && php artisan serve"
start "Admin 8090" cmd /k "cd /d C:\laragon\www\e-pkl\admin-web && php -S 127.0.0.1:8090 -t public"
start "Siswa 8081" cmd /k "cd /d C:\laragon\www\e-pkl\siswa-web && php -S 127.0.0.1:8081 -t public"