<?php
declare(strict_types=1);

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

function config(string $key, mixed $default = null): mixed
{
    static $cfg = null;
    $cfg ??= require BASE_PATH . '/config/config.php';
    $v = $cfg;
    foreach (explode('.', $key) as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) {
            return $default;
        }
        $v = $v[$k];
    }
    return $v;
}

/* ---------- Escape y formato ---------- */

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Traduce un texto (la clave es el texto en español). */
function t(string $key, array $vars = []): string
{
    return I18n::tr($key, $vars);
}

/** es: 1.234,56 €   ·   en: €1,234.56 */
function money(string|int|float|null $v): string
{
    if (I18n::lang() === 'en') {
        return '€' . number_format((float) $v, 2, '.', ',');
    }
    return number_format((float) $v, 2, ',', '.') . "\u{00A0}€";
}

function pct(float $v): string
{
    return I18n::lang() === 'en'
        ? number_format($v, 2, '.', ',') . '%'
        : number_format($v, 2, ',', '.') . "\u{00A0}%";
}

/** es: 15/01/2026   ·   en: 15 Jan 2026 */
function date_fmt(?string $d): string
{
    if (!$d) {
        return '—';
    }
    $dt = DateTime::createFromFormat('!Y-m-d', substr($d, 0, 10));
    if (!$dt) {
        return '—';
    }
    return $dt->format(I18n::lang() === 'en' ? 'd M Y' : 'd/m/Y');
}

function month_label(DateTimeInterface $d): string
{
    static $es = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    static $en = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $names = I18n::lang() === 'en' ? $en : $es;
    return $names[(int) $d->format('n') - 1] . ' ' . $d->format('Y');
}

/** Valor decimal para rellenar un campo de formulario (coma en español, punto en inglés). */
function form_decimal(string $v): string
{
    return I18n::lang() === 'es' ? str_replace('.', ',', $v) : $v;
}

/* ---------- Parseo y validación de entrada ---------- */

/** Acepta "1234.56", "1234,56", "1.234,56" o "1,234.56". Devuelve "1234.56" o null. */
function parse_money(string $raw): ?string
{
    $s = str_replace([' ', "\u{00A0}", '€'], '', trim($raw));
    if ($s === '') {
        return null;
    }
    $hasComma = str_contains($s, ',');
    $hasDot = str_contains($s, '.');
    if ($hasComma && $hasDot) {
        $s = strrpos($s, ',') > strrpos($s, '.')
            ? str_replace(',', '.', str_replace('.', '', $s))
            : str_replace(',', '', $s);
    } elseif ($hasComma) {
        $s = str_replace(',', '.', $s);
    }
    if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $s)) {
        return null;
    }
    return number_format((float) $s, 2, '.', '');
}

/** Valida una fecha Y-m-d real. Devuelve la misma cadena o null. */
function parse_date(string $raw): ?string
{
    $raw = trim($raw);
    $dt = DateTime::createFromFormat('!Y-m-d', $raw);
    if ($dt === false || $dt->format('Y-m-d') !== $raw || (int) $dt->format('Y') < 1990) {
        return null;
    }
    return $raw;
}

function like_escape(string $q): string
{
    return '%' . addcslashes($q, '%_\\') . '%';
}

/* ---------- Sesión: CSRF y mensajes flash ---------- */

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(mixed $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- URLs y navegación ---------- */

function url(string $path, array $query = []): string
{
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function current_path(): string
{
    $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return $p === '/' ? '/' : rtrim($p, '/');
}

function nav_active(string $prefix): bool
{
    $p = current_path();
    return $prefix === '/' ? $p === '/' : str_starts_with($p, $prefix);
}

/* ---------- Presentación ---------- */

function type_color(string $name, int $id): string
{
    $fixed = [
        'brokerage'       => '#0d6efd',
        'private markets' => '#6f42c1',
        'fixed income'    => '#198754',
        'crypto wallet'   => '#fd7e14',
    ];
    $key = mb_strtolower(trim($name));
    if (isset($fixed[$key])) {
        return $fixed[$key];
    }
    $palette = ['#0dcaf0', '#d63384', '#20c997', '#6c757d', '#ffc107', '#dc3545', '#8b5e3c'];
    return $palette[$id % count($palette)];
}

function type_badge(string $name, int $id): string
{
    return '<span class="badge badge-type" style="--tc:' . type_color($name, $id) . '">' . e($name) . '</span>';
}

/** Etiqueta de operación: Compra / Venta. */
function operation_badge(string $op): string
{
    $sell = $op === 'sell';
    return '<span class="badge badge-op badge-op-' . ($sell ? 'sell' : 'buy') . '"><i class="bi '
        . ($sell ? 'bi-arrow-up-right' : 'bi-arrow-down-left') . '"></i>' . e(t($sell ? 'Venta' : 'Compra')) . '</span>';
}

function invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

function feedback(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<div class="invalid-feedback">' . e($errors[$field]) . '</div>' : '';
}

function partial(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require BASE_PATH . "/views/partials/$name.php";
}

/* ---------- Días laborables (lun–vie, sin festivos) ---------- */

/** Primer día lun–vie del mes indicado. */
function first_business_day_of_month(int $year, int $month): DateTimeImmutable
{
    $d = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    while ((int) $d->format('N') > 5) {
        $d = $d->modify('+1 day');
    }
    return $d;
}

/** Primer día lun–vie igual o posterior al día $from del mes indicado. */
function first_business_day_from(int $year, int $month, int $from): DateTimeImmutable
{
    // Usamos format('t') (días del mes) en lugar de cal_days_in_month para no depender
    // de la extensión 'calendar', que no siempre está disponible en PHP 8 Docker.
    $firstOfMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $days = (int) $firstOfMonth->format('t');
    $from = min($from, $days);
    $d = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $from));
    while ((int) $d->format('N') > 5) {
        $d = $d->modify('+1 day');
    }
    return $d;
}

/**
 * Genera todas las fechas esperadas de una tarea programada entre $start y $until (inclusive).
 * Devuelve array de strings 'Y-m-d' ordenados ascendentemente.
 *
 * @param string $frequency  Valor del ENUM de scheduled_purchases.frequency
 */
function scheduled_expected_dates(string $start, string $frequency, string $until): array
{
    $startDt = new DateTimeImmutable($start);
    $untilDt = new DateTimeImmutable($until);

    if ($untilDt < $startDt) {
        return [];
    }

    $dates = [];

    if ($frequency === 'weekly') {
        // Primer lunes >= start_date
        $cursor = $startDt->modify('Monday this week');
        if ($cursor < $startDt) {
            $cursor = $cursor->modify('+7 days');
        }
        while ($cursor <= $untilDt) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor   = $cursor->modify('+7 days');
        }
        return $dates;
    }

    [$sy, $sm] = [(int) $startDt->format('Y'), (int) $startDt->format('n')];
    [$ey, $em] = [(int) $untilDt->format('Y'), (int) $untilDt->format('n')];

    $addStart = in_array($frequency, ['monthly_start', 'bimonthly', 'quarterly_start'], true);
    $addMid   = in_array($frequency, ['monthly_mid',   'bimonthly', 'quarterly_mid'],   true);

    // Para quarterly: calculamos el offset del mes inicial (0–2) para saber cada 3 meses
    $quarterOffset = ($sm - 1) % 3; // 0 si el mes inicial es ene/abr/jul/oct, etc.

    $y = $sy;
    $m = $sm;
    while ($y < $ey || ($y === $ey && $m <= $em)) {
        // ¿Es este mes un mes de ocurrencia para las frecuencias quarterly?
        $isQuarterMonth = (($m - 1) % 3) === $quarterOffset;

        if ($addStart && ($frequency !== 'quarterly_start' || $isQuarterMonth)) {
            $d = first_business_day_of_month($y, $m);
            if ($d >= $startDt && $d <= $untilDt) {
                $dates[] = $d->format('Y-m-d');
            }
        }

        if ($addMid && ($frequency !== 'quarterly_mid' || $isQuarterMonth)) {
            $d = first_business_day_from($y, $m, 15);
            if ($d >= $startDt && $d <= $untilDt) {
                $dates[] = $d->format('Y-m-d');
            }
        }

        if ($m === 12) {
            $y++;
            $m = 1;
        } else {
            $m++;
        }
    }

    sort($dates);
    return array_unique($dates);
}

/** Etiqueta legible de una frecuencia de tarea programada. */
function frequency_label(string $freq): string
{
    return match ($freq) {
        'weekly'          => t('Semanal (cada lunes)'),
        'monthly_start'   => t('Mensual (1.er día lab. del mes)'),
        'monthly_mid'     => t('Mensual (1.er día lab. desde el 15)'),
        'bimonthly'       => t('2 veces/mes (inicio y mediados)'),
        'quarterly_start' => t('Trimestral (1.er día lab. del mes)'),
        'quarterly_mid'   => t('Trimestral (1.er día lab. desde el 15)'),
        default           => e($freq),
    };
}
