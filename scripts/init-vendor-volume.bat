@echo off
setlocal
echo [init-vendor] Initializing copilot_poc vendor volume...

docker volume inspect admin_prj_laravel_vendor >nul 2>&1
if errorlevel 1 (
  echo ERROR: admin_prj_laravel_vendor not found. Start Docker once on the LLax27 side first.
  exit /b 1
)

docker volume create copilot_poc_laravel_vendor >nul 2>&1

docker run --rm ^
  -v admin_prj_laravel_vendor:/from:ro ^
  -v copilot_poc_laravel_vendor:/to ^
  alpine sh -c "cp -a /from/. /to/ && test -f /to/autoload.php && echo COPY_OK"

if errorlevel 1 (
  echo ERROR: Failed to copy vendor.
  exit /b 1
)

echo [init-vendor] Done: copilot_poc_laravel_vendor (independent from LLax27)
endlocal
