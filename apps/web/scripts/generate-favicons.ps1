Add-Type -AssemblyName System.Drawing

function New-ToursIconBitmap([int]$Size) {
  $bmp = New-Object System.Drawing.Bitmap $Size, $Size
  $g = [System.Drawing.Graphics]::FromImage($bmp)
  $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
  $g.Clear([System.Drawing.Color]::FromArgb(0, 0, 0, 0))

  $scale = $Size / 32.0
  $corner = [int](8 * $scale)
  if ($corner -lt 2) { $corner = 2 }

  $coral = [System.Drawing.Color]::FromArgb(255, 232, 98, 69)
  $teal = [System.Drawing.Color]::FromArgb(255, 46, 149, 149)
  $white = [System.Drawing.Color]::White

  $brush = New-Object System.Drawing.Drawing2D.LinearGradientBrush (
    (New-Object System.Drawing.Rectangle 0, 0, $Size, $Size),
    $coral,
    [System.Drawing.Color]::FromArgb(255, 212, 84, 56),
    45
  )
  $path = New-Object System.Drawing.Drawing2D.GraphicsPath
  $r = $corner
  $path.AddArc(0, 0, $r * 2, $r * 2, 180, 90)
  $path.AddArc($Size - $r * 2, 0, $r * 2, $r * 2, 270, 90)
  $path.AddArc($Size - $r * 2, $Size - $r * 2, $r * 2, $r * 2, 0, 90)
  $path.AddArc(0, $Size - $r * 2, $r * 2, $r * 2, 90, 90)
  $path.CloseFigure()
  $g.FillPath($brush, $path)

  $mountain = New-Object System.Drawing.Drawing2D.GraphicsPath
  $mountain.AddPolygon(@(
    (New-Object System.Drawing.PointF (5 * $scale), (23.5 * $scale)),
    (New-Object System.Drawing.PointF (11.2 * $scale), (15.8 * $scale)),
    (New-Object System.Drawing.PointF (15.2 * $scale), (19.2 * $scale)),
    (New-Object System.Drawing.PointF (20.4 * $scale), (11.5 * $scale)),
    (New-Object System.Drawing.PointF (27 * $scale), (23.5 * $scale))
  ))
  $g.FillPath((New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(245, 255, 255, 255))), $mountain)

  $trailPen = New-Object System.Drawing.Pen $teal, ([Math]::Max(1.2, 2.2 * $scale))
  $trailPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
  $trailPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
  $g.DrawBezier(
    $trailPen,
    (9 * $scale), (21.8 * $scale),
    (14 * $scale), (18 * $scale),
    (19 * $scale), (15 * $scale),
    (24.8 * $scale), (11.2 * $scale)
  )

  $dotR = [Math]::Max(1.5, 2.4 * $scale)
  $g.FillEllipse((New-Object System.Drawing.SolidBrush $teal), (24.8 * $scale) - $dotR, (11.2 * $scale) - $dotR, $dotR * 2, $dotR * 2)
  $inner = [Math]::Max(0.8, 1 * $scale)
  $g.FillEllipse((New-Object System.Drawing.SolidBrush $white), (24.8 * $scale) - $inner, (11.2 * $scale) - $inner, $inner * 2, $inner * 2)

  $g.Dispose()
  $brush.Dispose()
  $path.Dispose()
  return $bmp
}

$publicDir = Join-Path $PSScriptRoot '..\public'
$publicDir = [System.IO.Path]::GetFullPath($publicDir)

@(
  @{ File = 'favicon-16x16.png'; Size = 16 },
  @{ File = 'favicon-32x32.png'; Size = 32 },
  @{ File = 'apple-touch-icon.png'; Size = 180 }
) | ForEach-Object {
  $bmp = New-ToursIconBitmap $_.Size
  $bmp.Save((Join-Path $publicDir $_.File), [System.Drawing.Imaging.ImageFormat]::Png)
  $bmp.Dispose()
}

$bmp32 = New-ToursIconBitmap 32
$icoPath = Join-Path $publicDir 'favicon.ico'
if (Test-Path $icoPath) { Remove-Item $icoPath -Force }
$hIcon = $bmp32.GetHicon()
$icon = [System.Drawing.Icon]::FromHandle($hIcon)
$stream = [System.IO.File]::Create($icoPath)
$icon.Save($stream)
$stream.Close()
$icon.Dispose()
$bmp32.Dispose()
Write-Host "Favicons generated in $publicDir"
