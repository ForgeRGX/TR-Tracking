<?php
    /** @var string $content */
    $authed = Auth::check();
    $nav = [
        ['/', 'Dashboard', 'bi-speedometer2'],
        ['/portfolio', 'Portfolio', 'bi-pie-chart'],
        ['/investments', 'Inversiones', 'bi-cash-coin'],
        ['/assets', 'Activos', 'bi-collection'],
        ['/types', 'Tipos de activo', 'bi-tags'],
    ];
    $publicPaths = ['/login', '/register'];
    $path        = current_path();
?>
<!doctype html>
<html lang="<?= e(I18n::lang()) ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#e3f1ec">
        <link rel="icon" type="image/ico" href="/static/img/favicon.ico">
        <link rel="apple-touch-icon" href="/static/img/apple-touch-icon.png">
        <title><?= e($title ?? 'Inversiones') ?> <?= '• TR Tracking' ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Instrument+Sans:wght@400..700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="/static/css/app.css" rel="stylesheet">
    </head>
    <body>
        <?php if(!in_array($path,$publicPaths,true)): ?>
            <header class="app-header sticky-top">
                <div class="container">
                    <nav class="navbar navbar-expand-lg app-nav">
                        <a class="navbar-brand" href="/"><img src="/static/img/logo_letters.png" alt="" width="142" height="30" class="brand-mark"></a>
                        <?php if ($authed): ?>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                                aria-controls="mainNav" aria-expanded="false" aria-label="<?= e(t('Abrir menú')) ?>">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="mainNav">
                            <ul class="navbar-nav ms-auto">
                                <?php foreach ($nav as [$path, $label, $icon]): ?>
                                    <li class="nav-item">
                                        <a class="nav-link<?= nav_active($path) ? ' active' : '' ?>" href="<?= e($path) ?>">
                                            <i class="bi <?= e($icon) ?>"></i><?= e(t($label)) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="nav-user">
                                <a class="nav-user-name<?= nav_active('/settings') ? ' active' : '' ?>" href="/settings" title="<?= e(t('Configuración')) ?>"><i class="bi bi-person-circle"></i><?= e(Auth::name()) ?><i class="bi bi-gear ms-2 text-muted"></i></a>
                                <form method="post" action="/logout" class="m-0">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right"></i><?= t('Salir') ?></button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </nav>
                </div>
            </header>
        <?php endif; ?>

        <main class="container py-4">
            <?php foreach (flashes() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($f['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= e(t('Cerrar')) ?>"></button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </main>

        <footer class="container pb-4 text-center text-muted small">
            <?= t('Registro personal de inversiones (no se conecta a Trade Republic ni muestra valor de mercado)') ?>
            <br><br>
            <?php
                $appVersion = env('APP_VERSION', 'dev')    ?? 'dev';
                $appCommit  = env('APP_COMMIT', 'unknown') ?? 'unknown';
            ?>
            <a id="app-version-link" href="https://github.com/ForgeRGX/TR-Tracking" target="_blank">
                TR Tracking <span id="installed-version"><?= e($appVersion) ?></span> (<span id="installed-commit"><?= e($appCommit) ?></span>)
            </a>
            <span id="version-status" class="badge text-bg-secondary ms-1" role="status" aria-live="polite">Checking version...</span>
            <a id="version-release-link" href="#" class="ms-1" hidden>Show notes</a>
            <small id="version-checked" class="d-block mt-2"></small>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <?php if (!empty($withCharts)): ?>
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
            <script src="/static/js/charts.js"></script>
        <?php endif; ?>
        <script src="/static/js/app.js"></script>
        <script src="/static/js/version.js"></script>
    </body>
</html>