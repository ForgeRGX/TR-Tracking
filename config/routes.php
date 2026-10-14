<?php
declare(strict_types=1);

return [
    ['GET',  '/login',                    [AuthController::class, 'showLogin']],
    ['POST', '/login',                    [AuthController::class, 'login']],
    ['GET',  '/register',                 [AuthController::class, 'showRegister']],
    ['POST', '/register',                 [AuthController::class, 'register']],
    ['POST', '/logout',                   [AuthController::class, 'logout']],

    ['GET',  '/',                         [DashboardController::class, 'index']],
    ['GET',  '/settings',                 [SettingsController::class, 'index']],
    ['POST', '/settings/language',        [SettingsController::class, 'language']],
    ['POST', '/settings/password',        [SettingsController::class, 'password']],

    ['GET',  '/portfolio',                [PortfolioController::class, 'index']],

    ['GET',  '/investments',              [InvestmentController::class, 'index']],
    ['GET',  '/investments/create',       [InvestmentController::class, 'create']],
    ['GET',  '/investments/buy',          [InvestmentController::class, 'buy']],
    ['GET',  '/investments/sell',         [InvestmentController::class, 'sell']],
    ['POST', '/investments',              [InvestmentController::class, 'store']],
    ['GET',  '/investments/{id}/edit',    [InvestmentController::class, 'edit']],
    ['POST', '/investments/{id}',         [InvestmentController::class, 'update']],
    ['POST', '/investments/{id}/delete',  [InvestmentController::class, 'delete']],

    ['GET',  '/scheduled',                            [ScheduledPurchaseController::class, 'index']],
    ['GET',  '/scheduled/create',                     [ScheduledPurchaseController::class, 'create']],
    ['POST', '/scheduled',                            [ScheduledPurchaseController::class, 'store']],
    ['GET',  '/scheduled/{id}',                       [ScheduledPurchaseController::class, 'show']],
    ['GET',  '/scheduled/{id}/edit',                  [ScheduledPurchaseController::class, 'edit']],
    ['POST', '/scheduled/{id}',                       [ScheduledPurchaseController::class, 'update']],
    ['POST', '/scheduled/{id}/toggle',                [ScheduledPurchaseController::class, 'toggle']],
    ['POST', '/scheduled/{id}/delete',                [ScheduledPurchaseController::class, 'delete']],
    ['POST', '/scheduled/occurrences/{id}/confirm',   [ScheduledPurchaseController::class, 'confirmOccurrence']],
    ['POST', '/scheduled/occurrences/{id}/skip',      [ScheduledPurchaseController::class, 'skipOccurrence']],

    ['GET',  '/assets',                   [AssetController::class, 'index']],
    ['GET',  '/assets/create',            [AssetController::class, 'create']],
    ['POST', '/assets',                   [AssetController::class, 'store']],
    ['GET',  '/assets/{id}',              [AssetController::class, 'show']],
    ['GET',  '/assets/{id}/edit',         [AssetController::class, 'edit']],
    ['POST', '/assets/{id}',              [AssetController::class, 'update']],
    ['POST', '/assets/{id}/delete',       [AssetController::class, 'delete']],

    ['GET',  '/types',                    [AssetTypeController::class, 'index']],
    ['GET',  '/types/create',             [AssetTypeController::class, 'create']],
    ['POST', '/types',                    [AssetTypeController::class, 'store']],
    ['GET',  '/types/{id}/edit',          [AssetTypeController::class, 'edit']],
    ['POST', '/types/{id}',               [AssetTypeController::class, 'update']],
    ['POST', '/types/{id}/delete',        [AssetTypeController::class, 'delete']],

    ['GET', '/api/version',               [VersionController::class, 'index']],
];
