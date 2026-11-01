$ErrorActionPreference = "Stop"
$coversDir = "c:\Users\Aio\Downloads\Final V6 (2)\FinalV6\covers"

Write-Host "Scanning for duplicates in $coversDir..."
$images = Get-ChildItem -Path $coversDir -File | Where-Object { $_.Extension -ne ".zip" }
$hashes = @{}
$duplicatesCount = 0

foreach ($img in $images) {
    $hash = (Get-FileHash $img.FullName -Algorithm MD5).Hash
    if ($hashes.ContainsKey($hash)) {
        Write-Host "Deleting duplicate: $($img.Name)"
        Remove-Item $img.FullName -Force
        $duplicatesCount++
    } else {
        $hashes[$hash] = $img.FullName
    }
}

Write-Host "Removed $duplicatesCount duplicate images."

Write-Host "Deleting existing .zip files in $coversDir..."
$zipFiles = Get-ChildItem -Path $coversDir -Filter "*.zip" -File
foreach ($zip in $zipFiles) {
    Write-Host "Deleting zip: $($zip.Name)"
    Remove-Item $zip.FullName -Force
}

Write-Host "Zipping images in batches of 800..."
$uniqueImages = Get-ChildItem -Path $coversDir -File | Where-Object { $_.Extension -ne ".zip" } | Sort-Object Name
$totalImages = $uniqueImages.Count
$batchSize = 800
$batchCount = [Math]::Ceiling($totalImages / $batchSize)

Write-Host "Total unique images: $totalImages. Creating $batchCount zip archives."

for ($i = 0; $i -lt $batchCount; $i++) {
    $startIndex = $i * $batchSize
    $endIndex = [Math]::Min($startIndex + $batchSize - 1, $totalImages - 1)
    
    $batch = $uniqueImages[$startIndex..$endIndex]
    
    $zipName = "covers_part$($i + 1).zip"
    $zipPath = Join-Path -Path $coversDir -ChildPath $zipName
    
    Write-Host "Creating $zipName with $($batch.Count) images..."
    $batchPaths = $batch | Select-Object -ExpandProperty FullName
    Compress-Archive -Path $batchPaths -DestinationPath $zipPath -Force
}

Write-Host "Done! Zipped $totalImages images into $batchCount archives."
