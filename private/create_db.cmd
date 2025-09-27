@echo off
chcp 65001 >nul

if exist users.db (
  echo [91mJa existeix una base de dades anomenada "users.db"[0m
  echo.
  echo Elimina-la abans si la vols recrear des de zero
  goto finish
)

sqlite3 users.db "CREATE TABLE IF NOT EXISTS `users` (`user_id` INTEGER PRIMARY KEY, `user_name` varchar(63), `user_password` varchar(255), `user_mail` varchar (63));"
sqlite3 users.db "CREATE UNIQUE INDEX `user_name_UNIQUE` ON `users` (`user_name` ASC);"
sqlite3 users.db "CREATE UNIQUE INDEX `user_mail_UNIQUE` ON `users` (`user_mail` ASC);"

if exist users.db (
  echo [32mS'ha creat la base de dades "users.db"[0m
  echo.
  echo Si executes "sqlite3.exe" la pots carregar amb la comanda ".load users.db"
) else (
  echo [91mError: no s'ha pogut crear la base de dades "users.db"[0m
)

:finish
echo.
echo Prem qualsevol tecla per finalitzar...
pause >nul
