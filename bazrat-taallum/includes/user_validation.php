<?php
/**
 * قواعد التحقق من بيانات التسجيل والبروفايل:
 * اسم ثلاثي، عدم تطابق الاسم مع مستخدم آخر، طول رقم الهاتف المحلي.
 */

function normalize_full_name_for_uniqueness(string $name): string {
    $name = preg_replace('/\s+/u', ' ', trim($name));
    return mb_strtolower($name, 'UTF-8');
}

/** عدد أجزاء الاسم (كلمات مفصولة بمسافات) — يشترط ثلاثة على الأقل */
function full_name_part_count(string $name): int {
    $name = trim($name);
    if ($name === '') {
        return 0;
    }
    $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($parts) ? count($parts) : 0;
}

function is_full_name_taken(mysqli $conn, string $fullName, int $exceptUserId = 0): bool {
    $want = normalize_full_name_for_uniqueness($fullName);
    $res = $conn->query('SELECT id, full_name FROM users');
    if (!$res) {
        return false;
    }
    while ($row = $res->fetch_assoc()) {
        if ($exceptUserId > 0 && (int)$row['id'] === $exceptUserId) {
            continue;
        }
        if (normalize_full_name_for_uniqueness((string)$row['full_name']) === $want) {
            return true;
        }
    }
    return false;
}

/** أرقام الهاتف المحلي فقط (بدون مفتاح الدولة) */
function local_phone_digits(string $raw): string {
    return preg_replace('/\D+/', '', $raw);
}

/**
 * طول معقول للرقم الوطني بعد مفتاح الدولة (E.164: حتى 15 رقماً إجمالاً مع الدولة).
 * هنا نتحقق من الجزء المحلي: 6–12 رقماً.
 */
function is_local_phone_length_valid(string $localDigits): bool {
    $len = strlen($localDigits);
    return $len >= 6 && $len <= 12;
}

function validate_registration_fields(mysqli $conn, array $form, int $exceptUserId = 0): string {
    $fullName = trim((string)($form['full_name'] ?? ''));
    if ($fullName === '') {
        return 'الاسم الكامل مطلوب.';
    }
    if (full_name_part_count($fullName) < 3) {
        return 'الاسم يجب أن يكون ثلاثياً أو أكثر (ثلاث كلمات على الأقل).';
    }
    if (is_full_name_taken($conn, $fullName, $exceptUserId)) {
        return 'هذا الاسم مسجل مسبقاً، يرجى التأكد من عدم التطابق مع اسم مستخدم آخر.';
    }

    $cc = trim((string)($form['phone_country_code'] ?? ''));
    $local = local_phone_digits((string)($form['phone_number'] ?? ''));
    if ($cc === '' || $local === '') {
        return 'رقم الهاتف مطلوب.';
    }
    if (!is_local_phone_length_valid($local)) {
        return 'رقم الهاتف غير صالح: يجب أن يكون بين 6 و 12 رقماً (بدون مفتاح الدولة).';
    }

    return '';
}
