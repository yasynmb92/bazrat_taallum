$u = "http://localhost/learning-seeds2/bazrat-taallum"
$pages = @(
  "/index.php",
  "/courses.php",
  "/course.php?id=1",
  "/login.php",
  "/register.php",
  "/cart.php",
  "/checkout.php",
  "/my-learning.php",
  "/admin/index.php",
  "/index.php?lang=ar",
  "/index.php?lang=en"
)

foreach ($p in $pages) {
  try {
    $r = Invoke-WebRequest -UseBasicParsing -Uri ($u + $p) -Method GET -MaximumRedirection 0 -ErrorAction Stop
    Write-Output ($p + " => " + [string]$r.StatusCode)
  } catch {
    if ($_.Exception.Response) {
      Write-Output ($p + " => " + [string]$_.Exception.Response.StatusCode.value__)
    } else {
      Write-Output ($p + " => ERROR")
    }
  }
}
