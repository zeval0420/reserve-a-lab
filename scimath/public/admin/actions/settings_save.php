<?php
require_once __DIR__ . '/../../../src/bootstrap.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/index.php');
    exit;
}
Csrf::verify();

$eventId = (int) ($_POST['event_id'] ?? 0);
$settingsId = (int) ($_POST['settings_id'] ?? 0);
$event = Event::find($eventId);
$settings = $event !== null ? EventSetting::forEvent($eventId) : null;

if ($event === null || $settings === null || (int) $settings['id'] !== $settingsId) {
    Flash::error('That event/settings record could not be found.');
    header('Location: /admin/index.php');
    exit;
}

$v = new Validator();
$defaultPoints = $v->requiredInt($_POST, 'default_points', 'Default points', 0, 100000);
$defaultTime = $v->requiredInt($_POST, 'default_time_seconds', 'Default time', 5, 7200);
$rankingOrder = $v->inSet($_POST, 'ranking_order', 'Ranking order', RankingOrder::ALL);
$timerWarning = $v->optionalIntOrInherit($_POST, 'timer_warning_seconds', 'Timer warning', 0, 600);

if ($v->hasErrors()) {
    Flash::error('Please fix the highlighted errors: ' . implode(' ', $v->errors()));
    header('Location: /admin/event.php?id=' . $eventId . '#settings');
    exit;
}

$toggles = [
    'show_category'        => isset($_POST['show_category']),
    'show_question_number' => isset($_POST['show_question_number']),
    'show_timer'           => isset($_POST['show_timer']),
];

EventSetting::update($settingsId, [
    'default_points'                => $defaultPoints,
    'default_time_seconds'          => $defaultTime,
    'ranking_order'                 => $rankingOrder,
    'timer_warning_seconds'         => $timerWarning,
    'auto_show_ranking_after_score' => isset($_POST['auto_show_ranking_after_score']) ? 1 : 0,
    'extra_settings'                => $toggles,
]);

Flash::success('Settings saved.');
header('Location: /admin/event.php?id=' . $eventId . '#settings');
