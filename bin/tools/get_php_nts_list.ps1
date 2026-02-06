$url='https://windows.php.net/downloads/releases/archives/'
$url_root='https://windows.php.net/'
try {
	Invoke-WebRequest -UseBasicParsing -Uri $url | Select-Object -ExpandProperty Content | Select-String -AllMatches 'href=["'']([^"'']+x64\.zip)["'']' | ForEach-Object {
		$_.Matches | ForEach-Object {
			$link=$_.Groups[1].Value
			if ($link.StartsWith('/')) {
				$link -replace '^/', $url_root
			} else {
				$link
			}
		}
	} | findstr '\-nts\-' | findstr /V '\-pack\-' | Sort-Object -Descending
}
catch {
	Write-Error "Invoke-WebRequest falló: $($_.Exception.Message)"
	exit 1  # Error
}