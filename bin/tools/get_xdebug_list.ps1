$url='https://xdebug.org/download/historical'
$url_root='https://xdebug.org/'
try {
	Invoke-WebRequest -UseBasicParsing -Uri $url | Select-Object -ExpandProperty Content | Select-String -AllMatches 'href=["'']([^"'']+x86_64\.dll)["'']' | ForEach-Object {
		$_.Matches | ForEach-Object {
			$link=$_.Groups[1].Value
			if ($link.StartsWith('/')) {
				$link -replace '^/', $url_root
			} else {
				$link
			}
		}
	} | findstr '\-ts\-' | findstr 'php_xdebug' | findstr /V 'beta' | findstr /V 'alpha' | findstr /V '0rc' | findstr /V '1rc' | findstr /V '0RC' | findstr /V '1RC' | Sort-Object -Descending
}
catch {
	Write-Error "Invoke-WebRequest falló: $($_.Exception.Message)"
	exit 1  # Error
}