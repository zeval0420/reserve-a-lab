<?php

/**
 * JSON API for the operator interface (and, later, the public display).
 *
 * Every action here is a one-line call into CompetitionRuntime -- this file
 * contains no scoring or state-transition logic of its own. Its only job is
 * HTTP plumbing: auth, CSRF, parameter extraction, and translating
 * CompetitionRuntimeException into a clean JSON error response.
 *
 * Routing: ?action=<name>, GET for reads, POST for anything that mutates
 * state. All actions require an authenticated session (same Auth as the
 * admin interface -- there is no separate operator role, matching the
 * "prevent unauthorized access" requirement without building a full
 * multi-role account system). A future public-display stage would add a
 * separate, read-only, unauthenticated endpoint -- this one stays
 * operator-only.
 */

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function api_fail(int $httpStatus, string $message): void
{
    http_response_code($httpStatus);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function api_ok(array $data): void
{
    echo json_encode(['ok' => true] + $data);
    exit;
}

if (!Auth::check()) {
    api_fail(401, 'Not authenticated.');
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');
$isWrite = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isWrite && !Csrf::check()) {
    api_fail(419, 'Invalid or expired security token. Reload the page and try again.');
}

$input = $isWrite ? $_POST : $_GET;
$eventId = (int) ($input['event_id'] ?? 0);
if ($eventId <= 0) {
    api_fail(400, 'event_id is required.');
}

try {
    switch ($action) {
        // ---- Reads --------------------------------------------------
        case 'state':
            api_ok(CompetitionRuntime::getDashboard($eventId));
            break;

        case 'scores':
            $questionId = isset($input['question_id']) && $input['question_id'] !== ''
                ? (int) $input['question_id'] : null;
            api_ok(['scores' => CompetitionRuntime::getScores($eventId, $questionId)]);
            break;

        case 'rankings':
            api_ok(['rankings' => CompetitionRuntime::getRankings($eventId)]);
            break;

        // ---- Event lifecycle -----------------------------------------
        case 'start_event':
            require_write();
            CompetitionRuntime::startEvent($eventId);
            api_ok(CompetitionRuntime::getDashboard($eventId));
            break;

        case 'end_event':
            require_write();
            $state = CompetitionRuntime::endEvent($eventId);
            api_ok(['state' => $state]);
            break;

        // ---- Navigation ------------------------------------------------
        case 'start_first_question':
            require_write();
            api_ok(['state' => CompetitionRuntime::startFirstQuestion($eventId)]);
            break;

        case 'next_question':
            require_write();
            api_ok(['state' => CompetitionRuntime::nextQuestion($eventId)]);
            break;

        case 'previous_question':
            require_write();
            api_ok(['state' => CompetitionRuntime::previousQuestion($eventId)]);
            break;

        case 'go_to_question':
            require_write();
            $questionId = (int) ($input['question_id'] ?? 0);
            api_ok(['state' => CompetitionRuntime::goToQuestion($eventId, $questionId)]);
            break;

        // ---- Timer ----------------------------------------------------
        case 'start_timer':
            require_write();
            api_ok(['state' => CompetitionRuntime::startTimer($eventId)]);
            break;

        case 'pause_timer':
            require_write();
            api_ok(['state' => CompetitionRuntime::pauseTimer($eventId)]);
            break;

        case 'resume_timer':
            require_write();
            api_ok(['state' => CompetitionRuntime::resumeTimer($eventId)]);
            break;

        case 'reset_timer':
            require_write();
            api_ok(['state' => CompetitionRuntime::resetTimer($eventId)]);
            break;

        // ---- Display state ----------------------------------------------
        case 'show_ranking':
            require_write();
            api_ok(['state' => CompetitionRuntime::showRanking($eventId)]);
            break;

        case 'return_to_question':
            require_write();
            api_ok(['state' => CompetitionRuntime::returnToQuestion($eventId)]);
            break;

        case 'show_final_results':
            require_write();
            api_ok(['state' => CompetitionRuntime::showFinalResults($eventId)]);
            break;

        case 'return_to_cover':
            require_write();
            api_ok(['state' => CompetitionRuntime::returnToCover($eventId)]);
            break;

        // ---- Scoring ----------------------------------------------------
        case 'score':
            require_write();
            $questionId = (int) ($input['question_id'] ?? 0);
            $contestantId = (int) ($input['contestant_id'] ?? 0);
            $result = (string) ($input['result'] ?? '');
            $adjustmentPoints = isset($input['adjustment_points']) && $input['adjustment_points'] !== ''
                ? (int) $input['adjustment_points'] : null;
            $reason = isset($input['reason']) && $input['reason'] !== '' ? (string) $input['reason'] : null;

            $entry = CompetitionRuntime::scoreContestant(
                $eventId, $questionId, $contestantId, $result, $adjustmentPoints, Auth::username(), $reason
            );
            api_ok(['entry' => $entry] + CompetitionRuntime::getDashboard($eventId));
            break;

        default:
            api_fail(404, 'Unknown action.');
    }
} catch (CompetitionRuntimeException $e) {
    api_fail(422, $e->getMessage());
} catch (Throwable $e) {
    // Unexpected errors: don't leak internals to the client, but don't
    // silently swallow them either.
    error_log('[runtime API] ' . $e->getMessage());
    api_fail(500, 'An unexpected error occurred.');
}

function require_write(): void
{
    global $isWrite;
    if (!$isWrite) {
        api_fail(405, 'This action requires POST.');
    }
}
