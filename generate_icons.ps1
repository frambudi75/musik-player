Add-Type -AssemblyName System.Drawing
$dir = "e:\xampp\htdocs\music\assets\icons"
if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force }

function Generate-PngIcon($size, $filename) {
    $bmp = New-Object System.Drawing.Bitmap($size, $size)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

    $darkBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(15, 18, 26))
    $g.FillEllipse($darkBrush, 0, 0, $size, $size)

    $blueBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(59, 130, 246))
    $innerSize = [int]($size * 0.6)
    $innerPos = [int](($size - $innerSize) / 2)
    $g.FillEllipse($blueBrush, $innerPos, $innerPos, $innerSize, $innerSize)

    $centerBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(8, 9, 13))
    $centerSize = [int]($size * 0.22)
    $centerPos = [int](($size - $centerSize) / 2)
    $g.FillEllipse($centerBrush, $centerPos, $centerPos, $centerSize, $centerSize)

    $dotBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 255, 255))
    $dotSize = [int]($size * 0.08)
    $dotPos = [int](($size - $dotSize) / 2)
    $g.FillEllipse($dotBrush, $dotPos, $dotPos, $dotSize, $dotSize)

    $bmp.Save("$dir\$filename", [System.Drawing.Imaging.ImageFormat]::Png)
    $g.Dispose()
    $bmp.Dispose()
}

Generate-PngIcon 192 "icon-192.png"
Generate-PngIcon 512 "icon-512.png"
Generate-PngIcon 512 "icon-maskable.png"
Write-Host "Icons generated successfully!"
