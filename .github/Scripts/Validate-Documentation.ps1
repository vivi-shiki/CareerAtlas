# Validate the documentation foundation without changing repository files.
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$problems = [System.Collections.Generic.List[string]]::new()
$requiredFiles = @(
    'README.md', 'AGENTS.md', 'CLAUDE.md', 'Project.md',
    '.github/copilot-instructions.md',
    '.github/KnowledgeBase/Index.md',
    '.github/KnowledgeBase/Sources.md',
    '.github/KnowledgeBase/Learning.md',
    '.github/KnowledgeBase/KB_CareerAtlas_Product.md',
    '.github/KnowledgeBase/KB_CareerAtlas_FunctionalRequirements.md',
    '.github/KnowledgeBase/KB_CareerAtlas_Testing.md',
    '.github/KnowledgeBase/KB_CareerAtlas_AgentTools.md',
    '.github/KnowledgeBase/KB_CareerAtlas_API.md',
    '.github/KnowledgeBase/KB_CareerAtlas_Design_Architecture.md',
    '.github/KnowledgeBase/KB_CareerAtlas_Design_Deployment.md',
    '.github/KnowledgeBase/KB_CareerAtlas_Design_DataModel.md',
    '.github/KnowledgeBase/KB_CareerAtlas_Design_Authorization.md',
    '.github/KnowledgeBase/KB_CareerAtlas_Design_UserExperience.md',
    '.github/Guidelines/SourceFileManagement.md',
    '.github/Guidelines/Building.md',
    '.github/Guidelines/Testing.md',
    '.github/Guidelines/Debugging.md',
    '.github/Guidelines/Documentation.md',
    '.github/Learning/Learning_Coding.md',
    '.github/Learning/Learning_Testing.md',
    '.github/TaskLogs/README.md',
    '.github/TaskLogs/Copilot_Scrum.md',
    '.github/TaskLogs/Copilot_Task.md',
    '.github/TaskLogs/Copilot_Planning.md',
    '.github/TaskLogs/Copilot_Execution.md',
    '.github/TaskLogs/Copilot_Investigate.md',
    '.github/TaskLogs/Copilot_KB.md',
    '.github/prompts/0-scrum.prompt.md',
    '.github/prompts/1-design.prompt.md',
    '.github/prompts/2-planning.prompt.md',
    '.github/prompts/3-summarizing.prompt.md',
    '.github/prompts/4-execution.prompt.md',
    '.github/prompts/5-verifying.prompt.md',
    '.github/prompts/ask.prompt.md',
    '.github/prompts/code.prompt.md',
    '.github/prompts/investigate.prompt.md',
    '.github/prompts/review.prompt.md',
    '.github/prompts/kb.prompt.md',
    '.github/prompts/refine.prompt.md'
)

foreach ($relativePath in $requiredFiles) {
    if (-not (Test-Path -LiteralPath (Join-Path $repoRoot $relativePath) -PathType Leaf)) {
        $problems.Add("Missing required file: $relativePath")
    }
}

$documents = @(
    Get-ChildItem -LiteralPath $repoRoot -File -Filter '*.md'
    Get-ChildItem -LiteralPath (Join-Path $repoRoot '.github') -Recurse -File -Filter '*.md'
)
$linkCount = 0
foreach ($document in $documents) {
    $content = Get-Content -LiteralPath $document.FullName -Raw -Encoding UTF8
    if ([string]::IsNullOrWhiteSpace($content)) {
        $problems.Add("Empty document: $($document.FullName)")
        continue
    }
    # A consistency guard for this English baseline, not general language detection.
    if ($content -match '[\u3040-\u30ff\u3400-\u9fff]') {
        $problems.Add("Review non-English text in: $($document.FullName)")
    }
    if (([regex]::Matches($content, '(?m)^```').Count % 2) -ne 0) {
        $problems.Add("Unbalanced fenced code blocks: $($document.FullName)")
    }
    if ($content -match '(?m)[\t ]+\r?$') {
        $problems.Add("Trailing whitespace: $($document.FullName)")
    }
    foreach ($match in [regex]::Matches($content, '\[[^\]]+\]\(([^)]+)\)')) {
        $destination = $match.Groups[1].Value.Trim().Trim('<', '>')
        if ($destination -match '^[a-zA-Z][a-zA-Z0-9+.-]*:' -or $destination.StartsWith('#')) {
            continue
        }
        $localPath = [Uri]::UnescapeDataString(($destination -split '#', 2)[0])
        if ([string]::IsNullOrWhiteSpace($localPath)) { continue }
        $linkCount++
        $resolvedTarget = [IO.Path]::GetFullPath((Join-Path $document.DirectoryName $localPath))
        if (-not $resolvedTarget.StartsWith($repoRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
            $problems.Add("Local link leaves repository: $destination in $($document.FullName)")
        } elseif (-not (Test-Path -LiteralPath $resolvedTarget)) {
            $problems.Add("Broken local link: $destination in $($document.FullName)")
        }
    }
}

function Test-IdentifierSet {
    param([string]$RelativePath, [string]$Pattern, [string[]]$Expected, [string]$Label)
    $filePath = Join-Path $repoRoot $RelativePath
    if (-not (Test-Path -LiteralPath $filePath -PathType Leaf)) { return }
    $textContent = Get-Content -LiteralPath $filePath -Raw -Encoding UTF8
    $found = @([regex]::Matches($textContent, $Pattern) | ForEach-Object { $_.Groups[1].Value })
    foreach ($identifier in $Expected) {
        $count = @($found | Where-Object { $_ -eq $identifier }).Count
        if ($count -ne 1) { $problems.Add("${Label}: expected one $identifier, found $count") }
    }
    foreach ($identifier in $found) {
        if ($identifier -notin $Expected) { $problems.Add("${Label}: unexpected identifier $identifier") }
    }
}

$featureCounts = @(8, 8, 7, 8, 8)
$acceptanceCounts = @(5, 5, 6, 5, 6)
$expectedFeatures = @()
$expectedAcceptance = @()
for ($scenario = 1; $scenario -le 5; $scenario++) {
    for ($number = 1; $number -le $featureCounts[$scenario - 1]; $number++) {
        $expectedFeatures += ('S{0}-{1:D2}' -f $scenario, $number)
    }
    for ($number = 1; $number -le $acceptanceCounts[$scenario - 1]; $number++) {
        $expectedAcceptance += ('AC-S{0}-{1:D2}' -f $scenario, $number)
    }
}
Test-IdentifierSet '.github/KnowledgeBase/KB_CareerAtlas_FunctionalRequirements.md' '(?m)^\| (S[1-5]-\d{2}) \|' $expectedFeatures 'Features'
Test-IdentifierSet '.github/KnowledgeBase/KB_CareerAtlas_Testing.md' '(?m)^- (AC-S[1-5]-\d{2}):' $expectedAcceptance 'Acceptance criteria'
Test-IdentifierSet '.github/KnowledgeBase/KB_CareerAtlas_Testing.md' '(?m)^\| (T\d{2}) \|' @(1..12 | ForEach-Object { 'T{0:D2}' -f $_ }) 'Integration cases'

$markers = @{
    '.github/TaskLogs/Copilot_Scrum.md' = '# !!!SCRUM!!!'
    '.github/TaskLogs/Copilot_Task.md' = '# !!!TASK!!!'
    '.github/TaskLogs/Copilot_Planning.md' = '# !!!PLANNING!!!'
    '.github/TaskLogs/Copilot_Execution.md' = '# !!!EXECUTION!!!'
    '.github/TaskLogs/Copilot_Investigate.md' = '# !!!INVESTIGATE!!!'
    '.github/TaskLogs/Copilot_KB.md' = '# !!!KNOWLEDGE BASE!!!'
    '.github/KnowledgeBase/Learning.md' = '# !!!LEARNING!!!'
    '.github/Learning/Learning_Coding.md' = '# !!!LEARNING!!!'
    '.github/Learning/Learning_Testing.md' = '# !!!LEARNING!!!'
}
foreach ($entry in $markers.GetEnumerator()) {
    $markerPath = Join-Path $repoRoot $entry.Key
    if (Test-Path -LiteralPath $markerPath -PathType Leaf) {
        $firstLine = Get-Content -LiteralPath $markerPath -Encoding UTF8 -TotalCount 1
        if ($firstLine -ne $entry.Value) { $problems.Add("Invalid stage marker: $($entry.Key)") }
    }
}

if ($problems.Count -gt 0) {
    foreach ($problem in $problems) { Write-Output "FAIL: $problem" }
    Write-Output "Documentation validation failed: $($problems.Count) issue(s)."
    exit 1
}

Write-Output "PASS: $($documents.Count) Markdown documents; $($requiredFiles.Count) required files present."
Write-Output "PASS: $linkCount local links resolve inside the repository; external links and heading anchors are not checked."
Write-Output 'PASS: English-baseline character guard, code fences, and trailing whitespace.'
Write-Output 'PASS: 39 feature IDs, 27 acceptance IDs, and 12 integration-case IDs appear exactly once in canonical lists.'
Write-Output "PASS: $($markers.Count) task/learning markers."
Write-Output 'Scope: documentation consistency only; no application tests or live AI evaluation.'
exit 0
