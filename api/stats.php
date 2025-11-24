<?php
require_once __DIR__ . '/../includes/init.php';
$type = $_GET['type'] ?? 'region';
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$out = ['labels'=>[], 'data'=>[]];

// allow excluding superadmin reports when requesting overall stats
$exclude_superadmin = isset($_GET['exclude_superadmin']) && ($_GET['exclude_superadmin'] == '1' || strtolower($_GET['exclude_superadmin']) === 'true');

if ($type === 'region') {
    if ($user_filter) {
        $sql = "SELECT r.name AS label, COUNT(mi.id) AS value
                FROM regions r
                LEFT JOIN monitored_information mi ON mi.region_id = r.id AND mi.is_deleted = 0 AND mi.user_id = :uid
                GROUP BY r.id
                ORDER BY value DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid'=>$user_filter]);
        $rows = $stmt->fetchAll();
    } else {
        $sql = "SELECT r.name AS label, COUNT(mi.id) AS value
                FROM regions r
                LEFT JOIN monitored_information mi ON mi.region_id = r.id AND mi.is_deleted = 0
                GROUP BY r.id
                ORDER BY value DESC";
        $rows = $pdo->query($sql)->fetchAll();
    }

    foreach ($rows as $r) {
        $out['labels'][] = $r['label'];
        $out['data'][] = (int)$r['value'];
    }

} elseif ($type === 'user_timeline') {
    $period = $_GET['period'] ?? 'weekly';
    $uid = $user_filter;
    if (!$uid) { header('Content-Type: application/json'); echo json_encode($out); exit; }

    if ($period === 'monthly') {
        $rows = $pdo->prepare("SELECT DATE_FORMAT(event_date, '%Y-%m') AS label, COUNT(id) AS value
                              FROM monitored_information
                              WHERE is_deleted = 0 AND user_id = :uid AND event_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
                              GROUP BY DATE_FORMAT(event_date, '%Y-%m')
                              ORDER BY label ASC");
        $rows->execute([':uid'=>$uid]);
        $data = $rows->fetchAll();
        $dataMap = [];
        foreach ($data as $d) $dataMap[$d['label']] = (int)$d['value'];
        for ($i = 11; $i >= 0; $i--) {
            $dt = date('Y-m', strtotime("-{$i} months"));
            $out['labels'][] = $dt;
            $out['data'][] = $dataMap[$dt] ?? 0;
        }
    } else {
        $rows = $pdo->prepare("SELECT DATE(event_date) AS label, COUNT(id) AS value
                              FROM monitored_information
                              WHERE is_deleted = 0 AND user_id = :uid AND event_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                              GROUP BY DATE(event_date)
                              ORDER BY label ASC");
        $rows->execute([':uid'=>$uid]);
        $data = $rows->fetchAll();
        $dataMap = [];
        foreach ($data as $d) $dataMap[$d['label']] = (int)$d['value'];
        for ($i = 6; $i >= 0; $i--) {
            $dt = date('Y-m-d', strtotime("-{$i} days"));
            $out['labels'][] = $dt;
            $out['data'][] = $dataMap[$dt] ?? 0;
        }
    }

} elseif ($type === 'event_type') {
    // overall counts by event type
    if ($user_filter) {
        $sql = "SELECT et.name AS label, COUNT(mi.id) AS value
                FROM event_types et
                LEFT JOIN monitored_information mi ON mi.event_type_id = et.id AND mi.is_deleted = 0 AND mi.user_id = :uid
                GROUP BY et.id
                ORDER BY value DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $user_filter]);
        $rows = $stmt->fetchAll();
    } else {
        $sql = "SELECT et.name AS label, COUNT(mi.id) AS value
                FROM event_types et
                LEFT JOIN monitored_information mi ON mi.event_type_id = et.id AND mi.is_deleted = 0
                LEFT JOIN users u ON mi.user_id = u.id";
        if ($exclude_superadmin) {
            $sql .= " WHERE (u.role IS NULL OR u.role != 'superadmin')";
        }
        $sql .= " GROUP BY et.id ORDER BY value DESC";
        $rows = $pdo->query($sql)->fetchAll();
    }

    foreach ($rows as $r) {
        $out['labels'][] = $r['label'];
        $out['data'][] = (int)$r['value'];
    }

} else {
    $sql = "SELECT u.monitor_id_code AS label, COUNT(mi.id) AS value FROM users u
            LEFT JOIN monitored_information mi ON mi.user_id = u.id AND mi.is_deleted = 0
            WHERE 1=1";
    if ($user_filter) {
        $sql .= " AND u.id = :uid GROUP BY u.id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid'=>$user_filter]);
        $rows = $stmt->fetchAll();
    } else {
        $sql .= " GROUP BY u.id ORDER BY value DESC";
        $rows = $pdo->query($sql)->fetchAll();
    }
    foreach ($rows as $r) {
        $out['labels'][] = $r['label'];
        $out['data'][] = (int)$r['value'];
    }
}


header('Content-Type: application/json');
echo json_encode($out);
