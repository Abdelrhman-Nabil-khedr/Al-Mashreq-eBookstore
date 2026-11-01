$ErrorActionPreference = "Stop"
$jsonPath = "c:\Users\Aio\Downloads\Final V6\data\books.json"
$outputPath = "c:\Users\Aio\Downloads\Final V6\sitemap-books.xml"

Write-Host "Reading $jsonPath..."
$rawJson = [System.IO.File]::ReadAllText($jsonPath, [System.Text.Encoding]::UTF8)
$data = $rawJson | ConvertFrom-Json

$books = $data.books
Write-Host "Total book records in JSON: $($books.Count)"

$seen = New-Object 'System.Collections.Generic.HashSet[string]'
$urls = New-Object 'System.Collections.Generic.List[string]'

foreach ($b in $books) {
    $isbn = $b.ISBN
    if ($isbn) {
        $isbn = $isbn.ToString().Trim()
    }
    if (-not $isbn -and $b._id) {
        $isbn = $b._id.ToString().Trim()
    }
    if ($isbn -and -not $seen.Contains($isbn)) {
        [void]$seen.Add($isbn)
        $url = "https://almashreqebookstore.com/book/$isbn"
        [void]$urls.Add($url)
    }
}

Write-Host "Unique book URLs extracted: $($urls.Count)"

$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine('<?xml version="1.0" encoding="UTF-8"?>')
[void]$sb.AppendLine('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')

foreach ($u in $urls) {
    [void]$sb.AppendLine('  <url>')
    [void]$sb.AppendLine("    <loc>$u</loc>")
    [void]$sb.AppendLine('    <changefreq>monthly</changefreq>')
    [void]$sb.AppendLine('    <priority>0.8</priority>')
    [void]$sb.AppendLine('  </url>')
}

[void]$sb.AppendLine('</urlset>')

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($outputPath, $sb.ToString(), $utf8NoBom)

Write-Host "Successfully generated $outputPath ($($urls.Count) URLs)"
