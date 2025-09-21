@echo off
chcp 65001 >nul

cd /d "..\public\"

if not exist "..\PHP\php.exe" (
  echo [91mError: no s'ha trobat "..\PHP\php.exe"[0m
  echo.
  echo Assegura't que PHP es troba en aquesta ruta
  echo.
  echo Prem qualsevol tecla per finalitzar...
  pause >nul
  exit /b
)

title PHP @ %CD%
"..\PHP\php.exe" -S localhost:8000
