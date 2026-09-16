$ErrorActionPreference = "Stop"

$base = "http://localhost/learning-seeds2/bazrat-taallum"
$php = "C:\xampp\php\php.exe"
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Post-NoRedirect($url, $body) {
  return Invoke-WebRequest -UseBasicParsing -Uri $url -WebSession $sess -Method POST -Body $body -MaximumRedirection 0
}

$email = "e2e_user_$(Get-Date -Format 'yyyyMMddHHmmss')@example.com"
$fullName = "مستخدم E2E اختبار"
$birthYear = ((Get-Date).Year - 10).ToString()

Write-Output "E2E: Register user => $email"
$regBody = @{
  full_name = $fullName
  birth_year = $birthYear
  birth_month = "6"
  email = $email
  phone_country_code = "966"
  phone_number = "12345678"
  whatsapp = ""
  city = "Riyadh"
  state = "SA"
  parent_name = "Parent E2E"
  parent_phone = "98765432"
}
try { Post-NoRedirect ($base + "/register.php") $regBody | Out-Null } catch {}
Start-Sleep -Milliseconds 900

$dbUserCode = "require 'C:/xampp/htdocs/learning-seeds2/bazrat-taallum/config/db.php'; `$conn=db(); `$email=`$argv[1]; `$stmt=`$conn->prepare('SELECT id,verification_code,temp_password,email_verified FROM users WHERE email=? ORDER BY id DESC LIMIT 1'); `$stmt->bind_param('s',`$email); `$stmt->execute(); `$row=`$stmt->get_result()->fetch_assoc(); echo json_encode(`$row);"
$userJson = & $php -r $dbUserCode -- $email
$user = $userJson | ConvertFrom-Json
if (-not $user) { throw "User registration failed in E2E." }

Write-Output "E2E: Verify email with code => $($user.verification_code)"
try {
  Post-NoRedirect ($base + "/verify-email.php?email=" + [uri]::EscapeDataString($email)) @{ email = $email; code = $user.verification_code } | Out-Null
} catch {}
Start-Sleep -Milliseconds 500

$dbVerifyCode = "require 'C:/xampp/htdocs/learning-seeds2/bazrat-taallum/config/db.php'; `$conn=db(); `$email=`$argv[1]; `$stmt=`$conn->prepare('SELECT email_verified FROM users WHERE email=? ORDER BY id DESC LIMIT 1'); `$stmt->bind_param('s',`$email); `$stmt->execute(); `$row=`$stmt->get_result()->fetch_assoc(); echo json_encode(`$row);"
$vJson = & $php -r $dbVerifyCode -- $email
$v = $vJson | ConvertFrom-Json
Write-Output "E2E: email_verified => $($v.email_verified)"

Write-Output "E2E: Login"
try { Post-NoRedirect ($base + "/login.php") @{ email = $email; password = $user.temp_password } | Out-Null } catch {}
Start-Sleep -Milliseconds 400

$dbCourseCode = "require 'C:/xampp/htdocs/learning-seeds2/bazrat-taallum/config/db.php'; `$conn=db(); `$stmt=`$conn->prepare('SELECT id FROM courses WHERE is_published=1 AND is_free=1 ORDER BY id ASC LIMIT 1'); `$stmt->execute(); `$row=`$stmt->get_result()->fetch_assoc(); echo json_encode(`$row);"
$course = (& $php -r $dbCourseCode | ConvertFrom-Json)
if (-not $course) { throw "No free published course found." }

$courseId = [int]$course.id
Write-Output "E2E: Enroll free course => $courseId"
try { Post-NoRedirect ($base + "/course.php?id=" + $courseId) @{ enroll_free = "1" } | Out-Null } catch {}
Start-Sleep -Milliseconds 700

$dbLessonCode = "require 'C:/xampp/htdocs/learning-seeds2/bazrat-taallum/config/db.php'; `$conn=db(); `$cid=(int)`$argv[1]; `$stmt=`$conn->prepare('SELECT id FROM lessons WHERE course_id=? ORDER BY sort_order ASC, id ASC LIMIT 1'); `$stmt->bind_param('i',`$cid); `$stmt->execute(); `$row=`$stmt->get_result()->fetch_assoc(); echo json_encode(`$row);"
$lesson = (& $php -r $dbLessonCode -- $courseId | ConvertFrom-Json)

$dbQuizCode = "require 'C:/xampp/htdocs/learning-seeds2/bazrat-taallum/config/db.php'; `$conn=db(); `$cid=(int)`$argv[1]; `$stmt=`$conn->prepare('SELECT id,questions_json FROM course_quizzes WHERE course_id=? AND is_active=1 ORDER BY id DESC LIMIT 1'); `$stmt->bind_param('i',`$cid); `$stmt->execute(); `$row=`$stmt->get_result()->fetch_assoc(); echo json_encode(`$row);"
$quiz = (& $php -r $dbQuizCode -- $courseId | ConvertFrom-Json)
if (-not $quiz) { throw "No active quiz found for selected course." }

$questions = $quiz.questions_json | ConvertFrom-Json
$learnUrl = $base + "/learn.php?course_id=" + $courseId + "&lesson_id=" + $lesson.id

Write-Output "E2E: Submit quiz PASS"
$passBody = @{ course_id = $courseId; quiz_id = $quiz.id; submit_quiz_attempt = "1" }
for ($i=0; $i -lt $questions.Count; $i++) { $passBody["q_$i"] = [string]$questions[$i].answer }
try { Post-NoRedirect $learnUrl $passBody | Out-Null } catch {}
Start-Sleep -Milliseconds 300

$dbAttemptCode = "require 'C:/xampp/htdocs/learning-seeds2/bazrat-taallum/config/db.php'; `$conn=db(); `$qid=(int)`$argv[1]; `$uid=(int)`$argv[2]; `$stmt=`$conn->prepare('SELECT status,score FROM quiz_attempts WHERE quiz_id=? AND user_id=? ORDER BY id DESC LIMIT 1'); `$stmt->bind_param('ii',`$qid,`$uid); `$stmt->execute(); `$row=`$stmt->get_result()->fetch_assoc(); echo json_encode(`$row);"
$a1 = (& $php -r $dbAttemptCode -- $quiz.id $user.id | ConvertFrom-Json)
Write-Output "E2E: attempt#1 => status=$($a1.status), score=$($a1.score)"

Write-Output "E2E: Submit quiz FAIL"
$failBody = @{ course_id = $courseId; quiz_id = $quiz.id; submit_quiz_attempt = "1" }
for ($i=0; $i -lt $questions.Count; $i++) {
  $correct = [int]$questions[$i].answer
  $wrong = 0
  if ($correct -eq 0) { $wrong = 1 }
  $failBody["q_$i"] = [string]$wrong
}
try { Post-NoRedirect $learnUrl $failBody | Out-Null } catch {}
Start-Sleep -Milliseconds 300

$a2 = (& $php -r $dbAttemptCode -- $quiz.id $user.id | ConvertFrom-Json)
Write-Output "E2E: attempt#2 => status=$($a2.status), score=$($a2.score)"

Write-Output "E2E: Check outbox"
$dbOutboxCode = "require 'C:/xampp/htdocs/learning-seeds2/bazrat-taallum/config/db.php'; `$conn=db(); `$email=`$argv[1]; `$stmt=`$conn->prepare(\"SELECT subject,verification_code,sent_ok,html_preview FROM email_outbox WHERE email_to=? ORDER BY id DESC LIMIT 1\"); `$stmt->bind_param('s',`$email); `$stmt->execute(); `$row=`$stmt->get_result()->fetch_assoc(); echo json_encode(`$row);"
try {
  $o = (& $php -r $dbOutboxCode -- $email | ConvertFrom-Json)
  if ($o) {
    $sub = [string]$o.subject
    if ($sub.Length -gt 60) { $sub = $sub.Substring(0,60) }
    $hasCode = $false
    if ($o.html_preview -and $o.verification_code) { $hasCode = $o.html_preview.Contains([string]$o.verification_code) }
    Write-Output "E2E: outbox sent_ok=$($o.sent_ok), subject=`"$sub`", body_has_code=$hasCode"
  } else {
    Write-Output "E2E: no outbox row for this email."
  }
} catch {
  Write-Output "E2E: outbox query failed (table likely not migrated yet)."
}

Write-Output "E2E DONE"
