$files=("index.html", "blokhut_impressie.html", "calendar.html")
ForEach($file in $files){(Get-Content $file) | ForEach-Object {
$_.replace('href="css/style.css"', 'href="css/style.min.css"').`
replace('href="css/calendar.css"', 'href="css/calendar.min.css"')`
} | Set-Content $file}