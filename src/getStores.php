<?php
require_once __DIR__ . '/bootstrap.php';

// тут зарефаткорить (crsf, статусы и другое)

$stores = [];

$stmt = $db->prepare("
    SELECT id, name, address, work_hours, phone, latitude, longitude 
    FROM stores
");

if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $lat = $row['latitude'] !== null ? (float)$row['latitude'] : null;
        $lng = $row['longitude'] !== null ? (float)$row['longitude'] : null;

        $store = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'work_hours' => $row['work_hours'],
            'phone' => $row['phone']
        ];

        if ($lat !== null && $lng !== null) {
            $store['latitude'] = $lat;
            $store['longitude'] = $lng;
        }

        $stores[] = $store;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($stores, JSON_UNESCAPED_UNICODE);

if ($stmt) $stmt->close();
?>