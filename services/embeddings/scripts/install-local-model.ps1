# Copy intfloat/multilingual-e5-small from the HuggingFace hub cache into the repo.
# Run once after the model was downloaded (needs VPN/proxy if HF was blocked before).
# Usage: powershell -File services/embeddings/scripts/install-local-model.ps1

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$dest = Join-Path (Split-Path $PSScriptRoot -Parent) 'models\intfloat-multilingual-e5-small'
$hub = Join-Path $env:USERPROFILE '.cache\huggingface\hub\models--intfloat--multilingual-e5-small\snapshots'

if (-not (Test-Path $hub)) {
    Write-Error "HuggingFace cache not found at $hub. Download the model first (USE_STUB=false, one /embed call)."
}

$snapshot = Get-ChildItem $hub -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if (-not $snapshot) {
    Write-Error "No snapshot under $hub"
}

Write-Host "Copying from $($snapshot.FullName) to $dest ..."
New-Item -ItemType Directory -Force -Path $dest | Out-Null
robocopy $snapshot.FullName $dest /E /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
if ($LASTEXITCODE -ge 8) {
    Write-Error "robocopy failed with exit code $LASTEXITCODE"
}

$sizeMb = [math]::Round((Get-ChildItem $dest -Recurse -File | Measure-Object Length -Sum).Sum / 1MB, 1)
Write-Host "Done. Local model at $dest ($sizeMb MB). Set MODEL_ID=models/intfloat-multilingual-e5-small and USE_STUB=false."
