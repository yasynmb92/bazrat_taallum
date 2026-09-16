$u = "http://localhost/learning-seeds2/bazrat-taallum"
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Show-Result($label, $url) {
  try {
    $r = Invoke-WebRequest -UseBasicParsing -Uri $url -WebSession $sess -MaximumRedirection 0 -ErrorAction Stop
    Write-Output ($label + " => " + [string]$r.StatusCode)
    if ($r.Headers.Location) { Write-Output ("  Location: " + $r.Headers.Location) }
  } catch {
    if ($_.Exception.Response) {
      $resp = $_.Exception.Response
      $code = "N/A"
      try { $code = [string]([int]$resp.StatusCode) } catch {}
      Write-Output ($label + " => " + $code)

      $loc = $null
      try { $loc = $resp.Headers["Location"] } catch {}
      if (-not $loc) {
        try { $loc = $resp.GetResponseHeader("Location") } catch {}
      }
      if ($loc) { Write-Output ("  Location: " + $loc) }
    } else {
      Write-Output ($label + " => ERROR: " + $_.Exception.Message)
      if ($_.Exception.InnerException) {
        Write-Output ("  Inner: " + $_.Exception.InnerException.Message)
      }
    }
  }
}

Show-Result "cart (guest)" ($u + "/cart.php")
Show-Result "checkout (guest)" ($u + "/checkout.php")
Show-Result "my-learning (guest)" ($u + "/my-learning.php")
Show-Result "admin (guest)" ($u + "/admin/index.php")
