<?php
/**
 * دول ومفاتيح الاتصال للعرض في نموذج التسجيل.
 * @return list<array{code:string,dial:string,name_ar:string,name_en:string}>
 */
function countries_dial_list(): array {
    return [
        ['code' => 'SA', 'dial' => '+966', 'name_ar' => 'السعودية', 'name_en' => 'Saudi Arabia'],
        ['code' => 'AE', 'dial' => '+971', 'name_ar' => 'الإمارات', 'name_en' => 'United Arab Emirates'],
        ['code' => 'KW', 'dial' => '+965', 'name_ar' => 'الكويت', 'name_en' => 'Kuwait'],
        ['code' => 'QA', 'dial' => '+974', 'name_ar' => 'قطر', 'name_en' => 'Qatar'],
        ['code' => 'BH', 'dial' => '+973', 'name_ar' => 'البحرين', 'name_en' => 'Bahrain'],
        ['code' => 'OM', 'dial' => '+968', 'name_ar' => 'عُمان', 'name_en' => 'Oman'],
        ['code' => 'YE', 'dial' => '+967', 'name_ar' => 'اليمن', 'name_en' => 'Yemen'],
        ['code' => 'JO', 'dial' => '+962', 'name_ar' => 'الأردن', 'name_en' => 'Jordan'],
        ['code' => 'LB', 'dial' => '+961', 'name_ar' => 'لبنان', 'name_en' => 'Lebanon'],
        ['code' => 'SY', 'dial' => '+963', 'name_ar' => 'سوريا', 'name_en' => 'Syria'],
        ['code' => 'IQ', 'dial' => '+964', 'name_ar' => 'العراق', 'name_en' => 'Iraq'],
        ['code' => 'PS', 'dial' => '+970', 'name_ar' => 'فلسطين', 'name_en' => 'Palestine'],
        ['code' => 'IL', 'dial' => '+972', 'name_ar' => 'إسرائيل', 'name_en' => 'Israel'],
        ['code' => 'EG', 'dial' => '+20', 'name_ar' => 'مصر', 'name_en' => 'Egypt'],
        ['code' => 'SD', 'dial' => '+249', 'name_ar' => 'السودان', 'name_en' => 'Sudan'],
        ['code' => 'LY', 'dial' => '+218', 'name_ar' => 'ليبيا', 'name_en' => 'Libya'],
        ['code' => 'TN', 'dial' => '+216', 'name_ar' => 'تونس', 'name_en' => 'Tunisia'],
        ['code' => 'DZ', 'dial' => '+213', 'name_ar' => 'الجزائر', 'name_en' => 'Algeria'],
        ['code' => 'MA', 'dial' => '+212', 'name_ar' => 'المغرب', 'name_en' => 'Morocco'],
        ['code' => 'MR', 'dial' => '+222', 'name_ar' => 'موريتانيا', 'name_en' => 'Mauritania'],
        ['code' => 'US', 'dial' => '+1', 'name_ar' => 'الولايات المتحدة', 'name_en' => 'United States'],
        ['code' => 'GB', 'dial' => '+44', 'name_ar' => 'المملكة المتحدة', 'name_en' => 'United Kingdom'],
        ['code' => 'FR', 'dial' => '+33', 'name_ar' => 'فرنسا', 'name_en' => 'France'],
        ['code' => 'DE', 'dial' => '+49', 'name_ar' => 'ألمانيا', 'name_en' => 'Germany'],
        ['code' => 'TR', 'dial' => '+90', 'name_ar' => 'تركيا', 'name_en' => 'Turkey'],
        ['code' => 'IN', 'dial' => '+91', 'name_ar' => 'الهند', 'name_en' => 'India'],
        ['code' => 'PK', 'dial' => '+92', 'name_ar' => 'باكستان', 'name_en' => 'Pakistan'],
        ['code' => 'BD', 'dial' => '+880', 'name_ar' => 'بنغلاديش', 'name_en' => 'Bangladesh'],
        ['code' => 'ID', 'dial' => '+62', 'name_ar' => 'إندونيسيا', 'name_en' => 'Indonesia'],
        ['code' => 'MY', 'dial' => '+60', 'name_ar' => 'ماليزيا', 'name_en' => 'Malaysia'],
        ['code' => 'CN', 'dial' => '+86', 'name_ar' => 'الصين', 'name_en' => 'China'],
        ['code' => 'JP', 'dial' => '+81', 'name_ar' => 'اليابان', 'name_en' => 'Japan'],
        ['code' => 'KR', 'dial' => '+82', 'name_ar' => 'كوريا الجنوبية', 'name_en' => 'South Korea'],
        ['code' => 'RU', 'dial' => '+7', 'name_ar' => 'روسيا', 'name_en' => 'Russia'],
        ['code' => 'BR', 'dial' => '+55', 'name_ar' => 'البرازيل', 'name_en' => 'Brazil'],
        ['code' => 'NG', 'dial' => '+234', 'name_ar' => 'نيجيريا', 'name_en' => 'Nigeria'],
        ['code' => 'ZA', 'dial' => '+27', 'name_ar' => 'جنوب أفريقيا', 'name_en' => 'South Africa'],
        ['code' => 'KE', 'dial' => '+254', 'name_ar' => 'كينيا', 'name_en' => 'Kenya'],
        ['code' => 'ET', 'dial' => '+251', 'name_ar' => 'إثيوبيا', 'name_en' => 'Ethiopia'],
    ];
}

function countries_dial_sorted_for_lang(string $lang): array {
    $list = countries_dial_list();
    usort($list, function ($a, $b) use ($lang) {
        $na = $lang === 'ar' ? $a['name_ar'] : $a['name_en'];
        $nb = $lang === 'ar' ? $b['name_ar'] : $b['name_en'];
        return strcmp($na, $nb);
    });
    return $list;
}
