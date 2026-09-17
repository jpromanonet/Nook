<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/home', [HomeController::class, 'home']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);

$router->get('/onboarding', [OnboardingController::class, 'index']);
$router->post('/onboarding/name', [OnboardingController::class, 'saveName']);
$router->post('/onboarding/workspace', [OnboardingController::class, 'saveWorkspace']);
$router->post('/onboarding/task', [OnboardingController::class, 'saveTask']);
$router->post('/onboarding/finish', [OnboardingController::class, 'finish']);

$router->get('/today', [TodayController::class, 'index']);
$router->post('/today', [TodayController::class, 'save']);

$router->get('/board', [BoardController::class, 'index']);
$router->post('/board/move', [BoardController::class, 'move']);

$router->get('/calendar', [CalendarController::class, 'index']);
$router->get('/metrics', [MetricsController::class, 'index']);
$router->get('/search', [SearchController::class, 'index']);
$router->get('/search.json', [SearchController::class, 'json']);
$router->get('/favorites', [FavoritesController::class, 'index']);

$router->get('/settings', [SettingsController::class, 'index']);
$router->get('/settings/profile', [SettingsController::class, 'profile']);
$router->post('/settings/profile', [SettingsController::class, 'saveProfile']);
$router->post('/settings/avatar', [SettingsController::class, 'avatar']);
$router->post('/settings/avatar/remove', [SettingsController::class, 'avatarRemove']);
$router->get('/settings/account', [SettingsController::class, 'account']);
$router->post('/settings/account/delete', [SettingsController::class, 'deleteAccount']);
$router->get('/settings/appearance', [SettingsController::class, 'appearance']);
$router->post('/settings/appearance', [SettingsController::class, 'saveAppearance']);
$router->post('/settings/theme', [SettingsController::class, 'theme']);
$router->get('/settings/security', [SettingsController::class, 'security']);
$router->post('/settings/password', [SettingsController::class, 'password']);
$router->post('/settings/sessions/{id}/revoke', [SettingsController::class, 'revokeSession']);

$router->get('/workspaces', [WorkspaceController::class, 'index']);
$router->get('/workspaces/create', [WorkspaceController::class, 'create']);
$router->post('/workspaces', [WorkspaceController::class, 'store']);
$router->post('/workspaces/reorder', [WorkspaceController::class, 'reorder']);
$router->get('/workspaces/{id}', [WorkspaceController::class, 'show']);
$router->get('/workspaces/{id}/edit', [WorkspaceController::class, 'edit']);
$router->post('/workspaces/{id}', [WorkspaceController::class, 'update']);
$router->post('/workspaces/{id}/archive', [WorkspaceController::class, 'archive']);
$router->post('/workspaces/{id}/delete', [WorkspaceController::class, 'destroy']);
$router->get('/workspaces/{id}/documents', [WorkspaceController::class, 'module']);
$router->get('/workspaces/{id}/tasks', [WorkspaceController::class, 'module']);
$router->get('/workspaces/{id}/notes', [WorkspaceController::class, 'module']);
$router->get('/workspaces/{id}/files', [WorkspaceController::class, 'module']);
$router->get('/workspaces/{id}/media', [WorkspaceController::class, 'module']);
$router->get('/workspaces/{id}/links', [WorkspaceController::class, 'module']);
$router->get('/workspaces/{id}/accounts', [WorkspaceController::class, 'module']);
$router->get('/workspaces/{id}/archive', [WorkspaceController::class, 'module']);

$router->post('/uploads/chunk', [UploadController::class, 'chunk']);
$router->get('/items/create', [ItemController::class, 'create']);
$router->post('/items', [ItemController::class, 'store']);
$router->post('/items/reorder', [ItemController::class, 'reorder']);
$router->post('/items/move', [ItemController::class, 'move']);
$router->get('/items/{id}', [ItemController::class, 'show']);
$router->get('/items/{id}/edit', [ItemController::class, 'edit']);
$router->post('/items/{id}', [ItemController::class, 'update']);
$router->post('/items/{id}/archive', [ItemController::class, 'archive']);
$router->post('/items/{id}/restore', [ItemController::class, 'restore']);
$router->post('/items/{id}/delete', [ItemController::class, 'destroy']);
$router->post('/items/{id}/favorite', [ItemController::class, 'favorite']);
$router->post('/items/{id}/pin', [ItemController::class, 'pin']);
$router->post('/items/{id}/convert', [ItemController::class, 'convert']);
$router->post('/items/{id}/checklist/{index}', [ItemController::class, 'toggleChecklist']);
$router->get('/items/{id}/file', [FileController::class, 'item']);

$router->get('/avatar/{file}', [FileController::class, 'avatar']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
