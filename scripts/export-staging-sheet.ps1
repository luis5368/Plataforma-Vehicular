param(
    [Parameter(Mandatory=$true)][string]$Workbook,
    [Parameter(Mandatory=$true)][string]$Csv,
    [switch]$NormalizeCoordinateNoise
)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$source = (Resolve-Path -LiteralPath $Workbook).Path
$destination = [IO.Path]::GetFullPath($Csv)
if ([IO.File]::Exists($destination)) { throw 'The output already exists; choose a new private destination.' }
if (-not [IO.Directory]::Exists([IO.Path]::GetDirectoryName($destination))) { throw 'Create the private output directory first.' }
$archive = [IO.Compression.ZipFile]::OpenRead($source)
function Read-XmlEntry([string]$name) {
    $entry = $archive.GetEntry($name)
    if (-not $entry) { throw 'Missing workbook XML.' }
    $reader = [IO.StreamReader]::new($entry.Open())
    try { return [xml]$reader.ReadToEnd() } finally { $reader.Dispose() }
}
try {
    $strings = @()
    if ($archive.GetEntry('xl/sharedStrings.xml')) {
        $shared = Read-XmlEntry 'xl/sharedStrings.xml'
        $strings = @($shared.SelectNodes('//*[local-name()="si"]') | ForEach-Object { $_.InnerText })
    }
    $book = Read-XmlEntry 'xl/workbook.xml'
    $relations = Read-XmlEntry 'xl/_rels/workbook.xml.rels'
    $sheets = @($book.SelectNodes('//*[local-name()="sheet"]') | Where-Object name -eq 'CARGA_STAGING_CORREGIDA')
    if ($sheets.Count -ne 1) { throw 'The required sheet CARGA_STAGING_CORREGIDA is missing or ambiguous.' }
    $rid = $sheets[0].GetAttribute('id','http://schemas.openxmlformats.org/officeDocument/2006/relationships')
    $relation = $relations.Relationships.Relationship | Where-Object Id -eq $rid
    if ($relation.TargetMode -eq 'External') { throw 'External sheet not allowed.' }
    $target = [string]$relation.Target
    if ($target.StartsWith('/')) { $target=$target.TrimStart('/') } else { $target='xl/'+$target }
    if ($target.Contains('..')) { throw 'Invalid sheet path.' }
    $sheet = Read-XmlEntry $target
    $rows = $sheet.SelectNodes('//*[local-name()="sheetData"]/*[local-name()="row"]')
    $headers = $null
    $output = [Collections.Generic.List[object]]::new()
    $normalizedCoordinates = 0
    foreach ($row in $rows) {
        $cells = @{}
        foreach ($cell in $row.c) {
            if ($cell.f) { throw 'Formulas are not exported; provide literal corrected values.' }
            $letters = ([string]$cell.r) -replace '\d',''
            $index = 0
            foreach ($character in $letters.ToCharArray()) { $index=$index*26+([int]$character-64) }
            $value = if ($cell.t -eq 's') { $strings[[int]$cell.v] } elseif ($cell.t -eq 'inlineStr') { $cell.is.InnerText } else { [string]$cell.v }
            $cells[$index-1] = $value
        }
        if (-not $headers) {
            $headers = @(0..(($cells.Keys | Measure-Object -Maximum).Maximum) | ForEach-Object { [string]$cells[$_] })
            if ($headers.Count -ne 43 -or ($headers | Select-Object -Unique).Count -ne 43) { throw 'Invalid corrected headers.' }
        } else {
            if (@($cells.Keys | Where-Object { $_ -ge $headers.Count }).Count) { throw 'Unexpected extra cell.' }
            $record = [ordered]@{}
            for ($i=0;$i -lt $headers.Count;$i++) {
                $value = [string]$cells[$i]
                if ($NormalizeCoordinateNoise -and $headers[$i] -in @('theft_latitude','theft_longitude') -and $value -ne '') {
                    $number = [decimal]::Parse($value,[Globalization.NumberStyles]::Float,[Globalization.CultureInfo]::InvariantCulture)
                    $rounded = [decimal]::Round($number,8,[MidpointRounding]::AwayFromZero)
                    # Remove only Excel binary representation noise, not meaningful precision.
                    if ([Math]::Abs($number-$rounded) -gt [decimal]0.000000000001) { throw 'Coordinate precision exceeds the schema; correct the workbook explicitly.' }
                    if ($number -ne $rounded) { $normalizedCoordinates++ }
                    $value = $rounded.ToString('0.########',[Globalization.CultureInfo]::InvariantCulture)
                }
                $record[$headers[$i]]=$value
            }
            $output.Add([pscustomobject]$record)
        }
    }
    # Windows PowerShell writes UTF-8 BOM; PowerShell 7 UTF-8. Both are accepted.
    $output | Export-Csv -LiteralPath $destination -Encoding UTF8 -NoTypeInformation
    Write-Output ('Exported CARGA_STAGING_CORREGIDA: rows=' + $output.Count)
    Write-Output ('Coordinate binary-noise adjustments=' + $normalizedCoordinates)
} finally { $archive.Dispose() }
