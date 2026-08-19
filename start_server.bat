@echo off
title TCM Informed Consent - Server Launcher
color 0A

echo =================================================================
echo        MENJALANKAN SERVER TCM INFORMED CONSENT & NGROK
echo =================================================================
echo.

:: 1. Hentikan proses PHP / Ngrok lama jika masih berjalan
echo [*] Membersihkan proses lama...
taskkill /F /IM php.exe >nul 2>&1
taskkill /F /IM ngrok.exe >nul 2>&1

:: 2. Masuk ke direktori proyek
cd /d "%~dp0"

:: 3. Jalankan PHP Built-in Server di jendela terpisah
echo [*] Memulai PHP Server (localhost:8000)...
start "TCM - PHP Server" /MIN cmd /c "php -S localhost:8000 -t public"

:: Tunggu 2 detik agar PHP siap
timeout /t 2 /nobreak >nul

:: 4. Jalankan Ngrok
echo [*] Memulai Ngrok Tunnel (Port 8000)...
if exist "C:\Program Files\ngrok\ngrok.exe" (
    start "TCM - Ngrok Tunnel" "C:\Program Files\ngrok\ngrok.exe" http 8000
) else (
    start "TCM - Ngrok Tunnel" ngrok http 8000
)

echo.
echo =================================================================
echo   [OK] Server PHP dan Ngrok Berhasil Dijalankan!
echo.
echo   - Local URL : http://localhost:8000
echo   - Public URL: Lihat di jendela Ngrok yang terbuka
echo.
echo   Untuk menghentikan server, jalankan file stop_server.bat
echo =================================================================
echo.
timeout /t 5
