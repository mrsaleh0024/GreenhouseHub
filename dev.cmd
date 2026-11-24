@echo off
setlocal

rem The PHP first on this machine's PATH is a bare Windows build without
rem php.ini (no mbstring / pdo_mysql). Put the configured PHP first so the
rem dev server, queue worker and Vite all use the same, working binary.
set "PATH=C:\Users\ariunetpc6\AppData\Local\Programs\PHP;%PATH%"

call composer dev
