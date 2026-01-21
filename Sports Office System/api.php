<?php
header("Content-Type: application/json");

// DATABASE CONNECTION
$conn = new mysqli("localhost", "root", "", "sports_equipments_db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error"=>"DB Connection Failed"]);
    exit;
}

// GET ACTION
$action = $_GET['action'] ?? '';

// READ JSON BODY
$data = json_decode(file_get_contents("php://input"), true);

// =======================
// EQUIPMENT ACTIONS
// =======================

if ($action === "add_equipment") {
    $conn->query("INSERT INTO equipment
    (name, category, total, available, condition_status)
    VALUES (
    '{$data['name']}',
    '{$data['category']}',
    {$data['qty']},
    {$data['qty']},
    '{$data['condition']}')");
    echo json_encode(["status"=>"success"]);
}

// GET EQUIPMENT
elseif ($action === "get_equipment") {
    $res = $conn->query("SELECT * FROM equipment");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

// UPDATE EQUIPMENT
elseif ($action === "update_equipment") {
    $conn->query("UPDATE equipment SET
    name='{$data['name']}',
    category='{$data['category']}',
    total={$data['total']},
    available={$data['available']},
    condition_status='{$data['condition']}'
    WHERE id={$data['id']}");
    echo json_encode(["status"=>"updated"]);
}

// DELETE EQUIPMENT
elseif ($action === "delete_equipment") {
    $id = $_GET['id'];
    $conn->query("DELETE FROM equipment WHERE id=$id");
    echo json_encode(["status"=>"deleted"]);
}

// =======================
// BORROW ACTIONS
// =======================

elseif ($action === "borrow") {
    $conn->query("INSERT INTO borrowers
    (student_name, school_id, course, equipment_id, quantity, reason, status, borrowed_date)
    VALUES (
    '{$data['name']}',
    '{$data['school_id']}',
    '{$data['course']}',
    {$data['equipment_id']},
    {$data['qty']},
    '{$data['reason']}',
    'Borrowed',
    CURDATE()
    )");

    $conn->query("UPDATE equipment
    SET available = available - {$data['qty']}
    WHERE id = {$data['equipment_id']}");

    echo json_encode(["status"=>"borrowed"]);
}

// GET BORROWERS
elseif ($action === "get_borrowers") {
    $res = $conn->query("
    SELECT borrowers.*, equipment.name AS equipment_name
    FROM borrowers
    JOIN equipment ON borrowers.equipment_id = equipment.id
    ORDER BY borrowers.id DESC
    ");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

// RETURN EQUIPMENT
elseif ($action === "return") {
    $conn->query("UPDATE borrowers SET
    status='Returned',
    returned_date=CURDATE(),
    condition_returned='{$data['condition']}'
    WHERE id={$data['borrow_id']}");

    if ($data['condition'] !== 'bad') {
        $conn->query("UPDATE equipment
        SET available = available + {$data['qty']}
        WHERE id={$data['equipment_id']}");
    }

    echo json_encode(["status"=>"returned"]);
}

// =======================
// DEFAULT
// =======================
else {
    echo json_encode(["error"=>"Invalid action"]);
}

$conn->close();
