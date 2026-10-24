param([string]$MySqlBin = 'C:/Program Files/MySQL/MySQL Server 8.0/bin', [switch]$SessionOnly)
$ErrorActionPreference = 'Stop'
$project = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../..'))
$audit = [IO.Path]::GetFullPath((Join-Path $project 'storage/framework/production-audit'))
if (-not $audit.StartsWith($project + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Audit directory must stay inside the workspace.'
}
if (-not (Test-Path -LiteralPath (Join-Path $MySqlBin 'mysqld.exe'))) { throw 'MySQL server binary not found.' }
$listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, 33079)
try { $listener.Start() } finally { $listener.Stop() }
New-Item -ItemType Directory -Force -Path $audit | Out-Null
$marker = Join-Path $audit 'throwaway-instance.marker'
$data = Join-Path $audit 'mysql-data'
if ((Test-Path -LiteralPath $data) -and -not (Test-Path -LiteralPath $marker)) {
    throw 'Refusing to reuse an unmarked data directory.'
}
if (-not (Test-Path -LiteralPath $data)) {
    New-Item -ItemType Directory -Path $data | Out-Null
    New-Item -ItemType File -Path $marker | Out-Null
    & (Join-Path $MySqlBin 'mysqld.exe') --no-defaults --initialize-insecure "--datadir=$data" "--log-error=$audit/mysql-init.log"
    if ($LASTEXITCODE -ne 0) { throw 'MySQL initialization failed; details withheld.' }
}
$mysqlProcess = Start-Process -FilePath (Join-Path $MySqlBin 'mysqld.exe') -ArgumentList @(
    '--no-defaults', "--datadir=`"$data`"", '--port=33079', '--bind-address=127.0.0.1', '--mysqlx=OFF',
    '--skip-log-bin', "--pid-file=`"$audit/mysql.pid`"", "--log-error=`"$audit/mysql-run.log`""
) -WindowStyle Hidden -PassThru
try {
    $ready = $false
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        $socket = [Net.Sockets.TcpClient]::new()
        try {
            $socket.Connect('127.0.0.1', 33079)
            $ready = $true
            break
        } catch { } finally { $socket.Dispose() }
        Start-Sleep -Milliseconds 500
    }
    if (-not $ready) { throw 'Isolated MySQL did not become ready.' }
    Push-Location $project
    try {
        if ($SessionOnly) { & php tests/Production/mysql-audit.php --session-only }
        else { & php tests/Production/mysql-audit.php }
        if ($LASTEXITCODE -ne 0) { throw 'MySQL audit failed.' }
    } finally { Pop-Location }
} finally {
    & (Join-Path $MySqlBin 'mysqladmin.exe') --no-defaults --protocol=tcp --host=127.0.0.1 --port=33079 --user=root shutdown 2>$null | Out-Null
    if (-not $mysqlProcess.WaitForExit(10000)) { throw 'Audit MySQL still running: stop the recorded audit PID before proceeding.' }
}
