$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$command = Get-Command node -ErrorAction SilentlyContinue
$ready = $false
if ($command) {
    & $command.Source -e 'const [a,b]=process.versions.node.split(/[.]/).map(Number);process.exit(a>22||(a===22&&b>=13)?0:1)'
    $ready = $LASTEXITCODE -eq 0 -and [bool](Get-Command npm.cmd -ErrorAction SilentlyContinue)
}
if (-not $ready -or $env:AXIS_USE_PORTABLE_NODE -eq '1') {
    $architecture = if ($env:PROCESSOR_ARCHITECTURE -eq 'ARM64') {'arm64'} elseif ($env:PROCESSOR_ARCHITECTURE -eq 'AMD64') {'x64'} else {throw 'Windows x64 or ARM64 is required.'}
    $expected = if ($architecture -eq 'arm64') {'b12919e609b4fa1176ba8a155b49f761419a0c7cc97b42e6be09874a3f760ab6'} else {'bb819d6eb8f5bfda294bbc83a7e4ec6539da67c4233d54b0d655b9248b15e29d'}
    $directory = Join-Path $PSScriptRoot ".tools/node-v22.20.0-win-$architecture"
    if (-not (Test-Path -LiteralPath ($directory + '/node.exe'))) {
        New-Item -ItemType Directory -Force -Path .tools | Out-Null
        $archive = Join-Path $PSScriptRoot ".tools/node-v22.20.0-win-$architecture.zip"
        Write-Host 'Downloading project-local Node.js from nodejs.org...'
        Invoke-WebRequest -Uri "https://nodejs.org/dist/v22.20.0/node-v22.20.0-win-$architecture.zip" -OutFile $archive -UseBasicParsing
        if ((Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash.ToLower() -ne $expected) {throw 'Node.js checksum mismatch.'}
        Expand-Archive -LiteralPath $archive -DestinationPath .tools -Force
        Remove-Item -LiteralPath $archive
    }
    $env:PATH = $directory + ';' + $env:PATH
}
New-Item -ItemType Directory -Force -Path .tools | Out-Null
$current = (Get-FileHash -LiteralPath package-lock.json -Algorithm SHA256).Hash.ToLower()
$saved = if (Test-Path -LiteralPath .tools/installed-lock.sha256) {(Get-Content -LiteralPath .tools/installed-lock.sha256 -Raw).Trim()} else {''}
if (-not (Test-Path -LiteralPath node_modules/@wp-playground/wordpress) -or $saved -ne $current) {
    & npm.cmd ci --ignore-scripts --no-audit --no-fund
    if ($LASTEXITCODE -ne 0) {throw 'Dependency download failed. Check the network and rerun this launcher.'}
    [IO.File]::WriteAllText((Join-Path $PSScriptRoot '.tools/installed-lock.sha256'), $current)
}
& node scripts/start.mjs @args
exit $LASTEXITCODE
