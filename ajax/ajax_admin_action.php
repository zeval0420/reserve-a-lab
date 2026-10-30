<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require '../PHPMailer/src/Exception.php';
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';

    // centralized db_connection and session_handler
    include('../../scilab/helperFiles/db_connection.php');
    include('../helperFiles/session_handler.php');
    include('../helperFiles/variableDeclarations.php');
    include_once('../helperFiles/scilab_email.php');
    // Admin-only endpoint: logged in with the admin role, and a valid CSRF token on POST
    ral_require_role(['admin']);
    ral_require_csrf();

    // Get session data
    $email = $_SESSION['email'];
    $username   = $_SESSION['username'];
    $adminName = trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['middlename'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''));
    if ($adminName === '') {
        $adminName = $username;
    }

    function formatTime($time) {
        return date("g:i A", strtotime($time));
    }

    function scilab_fetch_request($conn, $requestID) {
        $stmt = $conn->prepare("SELECT * FROM scilab_form_requests WHERE id = ?");
        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    /**
     * Admin approve / force approve. Control number fields left blank are filled automatically
     * (scilab_approve_request -> scilab_assign_control_numbers). A value the admin typed (and
     * changed from the prefilled preview) goes through scilab_set_control_number, which also
     * shifts the later forms of that series.
     */
    function scilab_admin_approve($conn, $id, $adminName)
    {
        $remarks = $_POST['remarks'] ?? '';
        $typed = [];
        foreach (scilab_control_series() as $type => $col) {
            $value = trim($_POST[$col] ?? '');
            $edited = ($_POST[$col . '_edited'] ?? '1') !== '0';
            if ($value !== '' && $edited) {
                $typed[$type] = $value;
            }
        }

        // Control numbers are left alone here: blanks are auto-assigned inside the approval transaction
        $approval = scilab_approve_request($conn, $id, function ($conn) use ($adminName, $remarks, $id) {
            $stmt = $conn->prepare("UPDATE scilab_form_requests SET statusScilabPersonnel = 'Approved', supervisor_status = 'approved', subject_teacher_status = 'approved', lab_personnel_status = 'approved', cid_chief_status = 'approved', supervisor_approved_at = NOW(), subject_teacher_approved_at = NOW(), lab_personnel_approved_at = NOW(), cid_chief_approved_at = NOW(), supervisor_approved_by = ?, subject_teacher_approved_by = ?, lab_personnel_approved_by = ?, cid_chief_approved_by = ?, feedback = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $adminName, $adminName, $adminName, $adminName, $remarks, $id);
            $stmt->execute();
            $updated = $stmt->affected_rows > 0;
            $stmt->close();
            return $updated;
        });

        if (!$approval['ok']) {
            echo $approval['message'];
            return;
        }

        // Safety net: idempotent, only fills blanks (normally already done inside the approval)
        scilab_assign_control_numbers($conn, $id);

        $notes = [];
        $shifted = 0;
        foreach ($typed as $type => $value) {
            $r = scilab_set_control_number($conn, $id, $type, $value);
            if (!$r['ok']) {
                $notes[] = ucfirst($type) . ' control number not applied (' . $r['message'] . ')';
            } else {
                $shifted += $r['shifted'];
            }
        }

        $request = scilab_fetch_request($conn, $id);
        if ($request) {
            scilab_notify_stage_status($conn, $request, 'force_approve', 'approve');
        }
        echo "Request approved." . ($shifted ? " $shifted later form" . ($shifted === 1 ? '' : 's') . " shifted." : '') . ($notes ? ' ' . implode('; ', $notes) . '.' : '');
    }


    if (isset($_POST['action'])) {
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if ($_POST['action'] === 'check_conflict') {
            $id = $_POST['id'];

            // Get the request's date and time
            $stmt = $conn->prepare("SELECT inclusiveDate, inclusiveTime FROM scilab_form_requests WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                echo "not_found";
                exit();
            }
            $request = $result->fetch_assoc();
            $stmt->close();

            $date = $request['inclusiveDate'];
            $timeRange = explode(' to ', $request['inclusiveTime']);
            if (count($timeRange) !== 2) {
                echo "invalid_time_format";
                exit();
            }

            $startTime = strtotime($timeRange[0]);
            $endTime = strtotime($timeRange[1]);

            // Get all other approved requests on the same date
            $stmt = $conn->prepare("SELECT inclusiveTime FROM scilab_form_requests WHERE id != ? AND statusScilabPersonnel = 'Approved' AND inclusiveDate = ?");
            $stmt->bind_param("is", $id, $date);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $otherTimeRange = explode(' to ', $row['inclusiveTime']);
                if (count($otherTimeRange) !== 2) continue;

                $otherStart = strtotime($otherTimeRange[0]);
                $otherEnd = strtotime($otherTimeRange[1]);

                if ($startTime < $otherEnd && $endTime > $otherStart) {
                    echo "conflict";
                    exit();
                }
            }

            echo "no_conflict";
            exit();
        }
        elseif ($_POST['action'] === 'approve' || $_POST['action'] === 'force_approve') {
            scilab_admin_approve($conn, $id, $adminName);
            exit();
        }
        elseif ($_POST['action'] === 'next_control_numbers') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'numbers' => scilab_preview_control_numbers($conn)]);
            exit();
        }
        elseif ($_POST['action'] === 'update_control_numbers') {
            $current = scilab_fetch_request($conn, $id);
            if (!$current) {
                echo "Update failed.";
                exit();
            }

            // All changed fields in one transaction; each edit shifts the later forms of its series
            $conn->begin_transaction();
            $shifted = 0;
            $changed = 0;
            $errors = [];
            $noShift = [];
            try {
                foreach (scilab_control_series() as $type => $col) {
                    if (!array_key_exists($col, $_POST)) {
                        continue;
                    }
                    $value = trim($_POST[$col]);
                    if ($value === trim((string)($current[$col] ?? ''))) {
                        continue;
                    }
                    $r = scilab_set_control_number($conn, $id, $type, $value);
                    if (!$r['ok']) {
                        $errors[] = ucfirst($type) . ': ' . $r['message'];
                        break;
                    }
                    if ($r['changed']) {
                        $changed++;
                        $shifted += $r['shifted'];
                        if (!$r['shift'] && $value !== '') {
                            $noShift[] = ucfirst($type);
                        }
                    }
                }
                if ($errors) {
                    $conn->rollback();
                } else {
                    $conn->commit();
                }
            } catch (Throwable $e) {
                $conn->rollback();
                $errors[] = 'Update failed.';
            }

            if ($errors) {
                echo implode(' ', $errors);
            } elseif ($changed === 0) {
                echo "No changes made.";
            } else {
                echo "Control numbers updated." . ($shifted ? " $shifted later form" . ($shifted === 1 ? '' : 's') . " shifted." : " No later forms needed shifting.")
                    . ($noShift ? ' No shift for ' . implode(', ', $noShift) . ' (value has no trailing number).' : '');
            }
            exit();
        }
        elseif ($_POST['action'] === 'reject') {
            $feedback = $_POST['feedback'] ?? '';
            $stmt = $conn->prepare("UPDATE scilab_form_requests SET statusScilabPersonnel = 'Rejected', supervisor_status = 'rejected', subject_teacher_status = 'rejected', lab_personnel_status = 'rejected', cid_chief_status = 'rejected', controlNumber = NULL, control_equipment = NULL, control_reagent = NULL, control_permit = NULL, control_reservation = NULL, controlAssignedAt = NULL, feedback = ? WHERE id = ?");
            $stmt->bind_param("si", $feedback, $id);

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $request = scilab_fetch_request($conn, $id);
                if ($request) {
                    scilab_notify_stage_status($conn, $request, 'force_approve', 'reject', $feedback);
                }
                echo "Request rejected.";
            } else {
                echo "Update failed.";
            }
            $stmt->close();
            exit();
        }elseif ($_POST['action'] === 'generate_summary') {
            header('Content-Type: application/json');
            $startDate = $_POST['startDate'] ?? null;
            $endDate = $_POST['endDate'] ?? null;
            $classificationFilter = $_POST['classification'] ?? [];

            if (!$startDate || !$endDate) {
                echo json_encode(['success' => false, 'message' => 'Start and end dates are required.']);
                exit();
            }

            if (!is_array($classificationFilter)) {
                if ($classificationFilter === 'all' || $classificationFilter === '') {
                    $classifications = [];
                } else {
                    $classifications = explode(',', $classificationFilter);
                }
            } else {
                $classifications = $classificationFilter;
            }

            $filterByClassification = !empty($classifications) && !in_array('all', $classifications);

            $sql = "
                SELECT 
                    COALESCE(si.classification, 'Uncategorized') as classification,
                    mr.item, 
                    mr.description,
                    si.unit,
                    mr.quantity,
                    a.firstname,
                    a.middlename,
                    a.lastname,
                    st.firstname AS student_firstname,
                    st.middlename AS student_middlename,
                    st.lastname AS student_lastname,
                    fr.inclusiveDate,
                    fr.inclusiveTime,
                    fr.id AS formID,
                    fr.control_equipment,
                    fr.control_reagent,
                    fr.control_permit,
                    fr.control_reservation
                FROM scilab_material_requests mr
                JOIN scilab_form_requests fr ON mr.formID = fr.id
                LEFT JOIN scilab_inventory si ON mr.item = si.item
                LEFT JOIN accounts a ON fr.requesterEmployeeID = a.employeeID
                LEFT JOIN student st ON fr.requesterEmployeeID = st.LRN
                WHERE fr.statusScilabPersonnel = 'Approved'
                AND fr.inclusiveDate BETWEEN ? AND ?
            ";

            if ($filterByClassification) {
                $placeholders = implode(',', array_fill(0, count($classifications), '?'));
                $sql .= " AND COALESCE(si.classification, 'Uncategorized') IN ($placeholders) ";
            }

            $sql .= " ORDER BY COALESCE(si.classification, 'Uncategorized') ASC, mr.item ASC, fr.inclusiveDate ASC ";

            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Database query preparation failed.']);
                exit();
            }

            if ($filterByClassification) {
                $bindTypes = 'ss' . str_repeat('s', count($classifications));
                $bindParams = array_merge([$startDate, $endDate], $classifications);
                $stmt->bind_param($bindTypes, ...$bindParams);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $categorizedItems = [];
            while ($row = $result->fetch_assoc()) {
                $classification = $row['classification'];
                // Initialize the category array if it doesn't exist
                if (!isset($categorizedItems[$classification])) {
                    $categorizedItems[$classification] = [];
                }
                
                $requestorName = trim(
                    ($row['firstname'] ?? $row['student_firstname'] ?? '') . ' ' .
                    ($row['middlename'] ?? $row['student_middlename'] ?? '') . ' ' .
                    ($row['lastname'] ?? $row['student_lastname'] ?? '')
                );
                
                // Add the item to its category
                $categorizedItems[$classification][] = [
                    'item' => $row['item'], 
                    'description' => $row['description'], 
                    'quantity' => $row['quantity'], 
                    'unit' => $row['unit'],
                    'requestor' => $requestorName,
                    'date' => $row['inclusiveDate'],
                    'time' => $row['inclusiveTime'],
                    'formID' => $row['formID'],
                    'controlNumber' => scilab_control_for_classification($classification, $row)
                ];
            }

            echo json_encode(['success' => true, 'items' => $categorizedItems]);
            $stmt->close();
            exit();
        }

        $conn->close();
    }
?>