$files=("index.html", "calendar.html")
ForEach($file in $files){(Get-Content $file) | ForEach-Object {
$_.replace('src="js/scripts.origineel.js"', 'src="js/scripts.js"').`
replace('src="js/calendar.origineel.js"', 'src="js/calendar.js"')`
} | Set-Content $file}