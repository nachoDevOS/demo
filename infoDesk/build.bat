@echo off
echo ============================================
echo  infoDesk - Generando ejecutable .exe
echo ============================================
echo.

pip install -r requirements.txt

pyinstaller ^
  --onefile ^
  --windowed ^
  --icon=icono.ico ^
  --name=infoDesk ^
  --add-data "config.ini;." ^
  --add-data "notification.mp3;." ^
  --add-data "icono.ico;." ^
  --noupx ^
  cliente.py

echo.
echo ============================================
echo  Listo! El ejecutable esta en dist\infoDesk.exe
echo  Copiar MensaDesk.exe y config.ini a cada PC.
echo ============================================
pause
