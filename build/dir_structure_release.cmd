@echo off
echo Check directory strcuture
:: first cehck the directory structure
if not exist "Release" mkdir Release
if not exist "Release\js" mkdir Release\js
if not exist "Release\css" mkdir Release\css
if not exist "Release\images" mkdir Release\images
if not exist "Release\images\favicons" mkdir Release\images\favicons
if not exist "Release\cron" mkdir Release\cron
if not exist "Release\php" mkdir Release\php
if not exist "Release\php\templates" mkdir Release\php\templates
if not exist "Release\fonts" mkdir Release\fonts

echo Copying HTML
:: copy html files and icon
copy /y index.html Release\index.html
copy /y favicon.ico Release\favicon.ico
copy /y calendar.html Release\calendar.html
::copy /y blokhut_impressie.html Release\blokhut_impressie.html

echo Copying JavaScript
:: copy js files
copy /y js\scripts.js Release\js\scripts.js
copy /y js\calendar.js Release\js\calendar.js

echo Copying CSS
::copy css files
copy /y css\style.min.css Release\css\style.min.css
copy /y css\calendar.min.css Release\css\calendar.min.css

echo Copying images
:: copy the entire images directory
xcopy /y /s images Release\images

echo Copying fonts
:: copy the fonts directory
xcopy /y /s fonts Release\fonts

echo Copying PHP
:: copy php files
copy /y php\contact-form.php Release\php\contact-form.php
copy /y php\date_layer.php Release\php\date_layer.php
copy /y php\db_layer.php Release\php\db_layer.php
copy /y php\mail_layer.php Release\php\mail_layer.php
copy /y php\huurovereenkomst.php Release\php\huurovereenkomst.php
copy /y php\kostenberekening.php Release\php\kostenberekening.php
copy /y php\templates\huurovereenkomst.tpl Release\php\templates\huurovereenkomst.tpl
copy /y php\templateParser.php Release\php\templateParser.php
copy /y php\Reservering.php Release\php\Reservering.php
copy /y php\ReserveringVerification.php Release\php\ReserveringVerification.php
copy /y php\ReserveringMonth.php Release\php\ReserveringMonth.php
copy /y php\verhuur_settings.php Release\php\verhuur_settings.php
copy /y php\verhuur-confirm.php Release\php\verhuur-confirm.php
copy /y php\verhuur-form.php Release\php\verhuur-form.php

echo Copying cron jobs
:: copy cron job files
copy /y cron\poetsrooster.php Release\cron\poetsrooster.php
copy /y cron\batch.php Release\cron\batch.php

pause
