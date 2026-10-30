<?php
/**
 * stage_flow.php - approval stage order for a scilab_form_requests row.
 *
 * Stage keys and DB columns never change: supervisor_*, subject_teacher_*,
 * lab_personnel_*, cid_chief_*.
 *
 * Two flows exist:
 *   - Subject-teacher flow (student requests that carry a `subjectTeacher`):
 *       subject_teacher -> supervisor -> lab_personnel -> cid_chief
 *     Here subject_teacher_* means the SELECTED subject teacher and supervisor_*
 *     means the teacher-in-charge.
 *   - Legacy flow (faculty requests, and student rows saved before the field
 *     existed, i.e. subjectTeacher = ''):
 *       supervisor -> subject_teacher -> lab_personnel -> cid_chief
 *     Here subject_teacher_* still means the Area Unit Head (AUH).
 *
 * Every place that hard-codes the order should go through these helpers.
 */

if (!function_exists('scilab_is_subject_teacher_flow')) {

    /** Is this request on the new subject-teacher-first flow? */
    function scilab_is_subject_teacher_flow($request)
    {
        return trim((string)($request['subjectTeacher'] ?? '')) !== '';
    }

    /** Ordered list of stage keys for this request. */
    function scilab_stage_order($request)
    {
        if (scilab_is_subject_teacher_flow($request)) {
            return ['subject_teacher', 'supervisor', 'lab_personnel', 'cid_chief'];
        }
        return ['supervisor', 'subject_teacher', 'lab_personnel', 'cid_chief'];
    }

    /** Position of a stage in this request's order, or -1. */
    function scilab_stage_index($request, $stage)
    {
        $idx = array_search($stage, scilab_stage_order($request), true);
        return $idx === false ? -1 : (int)$idx;
    }

    /**
     * The stage waiting for action: the first stage in order whose status is
     * 'pending'. Returns '' when a stage was rejected first or nothing is pending.
     */
    function scilab_current_stage($request)
    {
        foreach (scilab_stage_order($request) as $stage) {
            $status = strtolower(trim((string)($request[$stage . '_status'] ?? 'pending')));
            if ($status === 'pending') {
                return $stage;
            }
            if ($status === 'rejected') {
                return '';
            }
        }
        return '';
    }

    /** Stage after $stage in this request's order, or '' when it is the last. */
    function scilab_next_stage($request, $stage)
    {
        $order = scilab_stage_order($request);
        $idx = array_search($stage, $order, true);
        if ($idx === false) return '';
        return $order[$idx + 1] ?? '';
    }

    /** Stages strictly before $stage in this request's order. */
    function scilab_prior_stages($request, $stage)
    {
        $order = scilab_stage_order($request);
        $idx = array_search($stage, $order, true);
        if ($idx === false) return [];
        return array_slice($order, 0, (int)$idx);
    }

    /** $stage and every stage after it. */
    function scilab_stages_from($request, $stage)
    {
        $order = scilab_stage_order($request);
        $idx = array_search($stage, $order, true);
        if ($idx === false) return [];
        return array_slice($order, (int)$idx);
    }

    /** Human-readable role label for a stage, following the request's flow. */
    function scilab_stage_label($request, $stage)
    {
        $newFlow = scilab_is_subject_teacher_flow($request);
        $labels = [
            'supervisor' => $newFlow ? 'Teacher-in-Charge' : 'Supervisor',
            'subject_teacher' => $newFlow ? 'Subject Teacher' : 'Area Unit Head (AUH)',
            'lab_personnel' => 'Lab Personnel',
            'cid_chief' => 'CID Chief',
            'force_approve' => 'Administrator (Force Approval)',
        ];
        return $labels[$stage] ?? ucwords(str_replace('_', ' ', (string)$stage));
    }

    /**
     * Does a "Last, First M." string stored on a request (teacherInCharge /
     * subjectTeacher, possibly several names comma-joined) refer to this account?
     * $user needs firstname, middlename, lastname. Same rule the teacher-in-charge
     * check always used.
     */
    function scilab_name_matches_user(array $user, $storedNames)
    {
        $formatted = trim(($user['lastname'] ?? '') . ', ' . ($user['firstname'] ?? '') . ' ' . ($user['middlename'] ?? ''));
        return $formatted !== ',' && strpos((string)$storedNames, $formatted) !== false;
    }
}
