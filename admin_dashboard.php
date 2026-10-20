<?php
session_start();

// Prototype has no server-side auth, so the admin panel only answers local requests.
// shortcut: REMOTE_ADDR check only, behind a reverse proxy every request looks local; add real auth before any deployment.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Admin panel is only available on localhost in this prototype.');
}

/**
 * EMU Activity & Event Reporting Portal
 * Admin Dashboard v2.0
 * * Features: Persistent JSON Storage, Multi-select Inputs, Improved CSV, Counting Logic.
 */

/* ---------- CONFIGURATION & LISTS ---------- */
$json_file = 'events.json';

// Define Standard Lists for Multi-Select Forms
$sdg_list = [
    "SDG 1: No Poverty", "SDG 2: Zero Hunger", "SDG 3: Good Health and Well-being",
    "SDG 4: Quality Education", "SDG 5: Gender Equality", "SDG 6: Clean Water and Sanitation",
    "SDG 7: Affordable and Clean Energy", "SDG 8: Decent Work and Economic Growth",
    "SDG 9: Industry, Innovation and Infrastructure", "SDG 10: Reduced Inequalities",
    "SDG 11: Sustainable Cities and Communities", "SDG 12: Responsible Consumption and Production",
    "SDG 13: Climate Action", "SDG 14: Life Below Water", "SDG 15: Life on Land",
    "SDG 16: Peace, Justice and Strong Institutions", "SDG 17: Partnerships for the Goals"
];

$the_list = ["Teaching", "Research", "Citations Impact", "International Outlook", "Industry Income / Collaboration"];
$qs_list = ["Teaching & Learning", "Employability", "Research Quality", "Internationalization", "Inclusiveness", "Social Responsibility"];

/* ---------- 1) INITIALIZE DATA ---------- */
// Load from JSON into Session
if (file_exists($json_file)) {
    $data = file_get_contents($json_file);
    $_SESSION['events'] = json_decode($data, true) ?? [];
} else {
    $_SESSION['events'] = [];
}

$events = &$_SESSION['events'];

/* ---------- 2) HELPERS ---------- */
function redirect_home() {
    header("Location: admin_dashboard.php");
    exit;
}

function next_event_id($events) {
    $max = 0;
    foreach ($events as $e) $max = max($max, (int)$e['id']);
    return $max + 1;
}

function find_event_index_by_id($events, $id) {
    foreach ($events as $i => $e) {
        if ((int)$e['id'] === (int)$id) return $i;
    }
    return -1;
}

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// NEW: Save changes to JSON file
function save_events_to_file($events_data) {
    global $json_file;
    // JSON_PRETTY_PRINT for readability, JSON_UNESCAPED_UNICODE for Turkish chars
    file_put_contents($json_file, json_encode($events_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/* ---------- 3) ACTIONS: EXPORT CSV ---------- */
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=emu_events_export.csv');

    $out = fopen('php://output', 'w');
    
    // NEW: Add BOM for Excel compatibility (Fixes Turkish characters)
    fputs($out, "\xEF\xBB\xBF");

    // Headers
    fputcsv($out, ['ID', 'Title', 'Date', 'Time', 'Organizer', 'SDGs', 'THE Categories', 'QS Categories', 'Description', 'Participants', 'Status']);

    foreach ($events as $e) {
        fputcsv($out, [
            $e['id'], 
            $e['title'], 
            $e['date'], 
            $e['time'], 
            $e['organizer'],
            $e['sdg'],      // Now handles comma-separated strings nicely
            $e['the_cat'], 
            $e['qs_cat'], 
            $e['description'],
            $e['participants'] ?? 'N/A',
            $e['status'] ?? 'N/A'
        ]);
    }
    fclose($out);
    exit;
}

/* ---------- 4) ACTIONS: CRUD WITH JSON SAVE ---------- */
$message = '';
$save_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Helper to handle multi-select arrays -> string
    $process_multiselect = function($post_key) {
        $data = $_POST[$post_key] ?? [];
        if (is_array($data)) {
            return implode(', ', $data); // Convert ["SDG 1", "SDG 2"] to "SDG 1, SDG 2"
        }
        return trim($data);
    };

    // ADD EVENT
    if ($action === 'add_event') {
        $new = [
            'id' => next_event_id($events),
            'title' => trim($_POST['title'] ?? ''),
            'title_tr' => trim($_POST['title_tr'] ?? ''),
            'date' => trim($_POST['date'] ?? ''),
            'time' => trim($_POST['time'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'description_tr' => trim($_POST['description_tr'] ?? ''),
            'sdg' => $process_multiselect('sdg'),         // Multi-select handler
            'the_cat' => $process_multiselect('the_cat'), // Multi-select handler
            'qs_cat' => $process_multiselect('qs_cat'),   // Multi-select handler
            'organizer' => trim($_POST['organizer'] ?? ''),
            'organizer_tr' => trim($_POST['organizer'] ?? ''),
            'image' => trim($_POST['image'] ?? ''),
            'location' => trim($_POST['location'] ?? 'TBA'),
            'location_tr' => trim($_POST['location'] ?? 'TBA'),
            'participants' => trim($_POST['participants'] ?? '0'),
            'status' => trim($_POST['status'] ?? 'Planned'),
            'contact' => trim($_POST['contact'] ?? 'Admin'),
            'contact_tr' => trim($_POST['contact'] ?? 'Admin')
        ];

        if ($new['title'] === '' || $new['date'] === '') {
            $message = 'Error: Title and Date are required.';
        } else {
            if ($new['image'] === '') $new['image'] = 'https://via.placeholder.com/600x400?text=New+Event';
            $events[] = $new;
            save_events_to_file($events); // Write EN to events.json
            
            // Also save TR version to eventstr.json
            $events_tr_file = 'eventstr.json';
            $events_tr = file_exists($events_tr_file) ? json_decode(file_get_contents($events_tr_file), true) ?? [] : [];
            $new_tr = $new;
            $new_tr['title'] = $new['title_tr'];
            $new_tr['description'] = $new['description_tr'];
            $new_tr['organizer'] = $new['organizer_tr'] ?? $new['organizer'];
            $new_tr['location'] = $new['location_tr'] ?? $new['location'];
            $new_tr['contact'] = $new['contact_tr'] ?? $new['contact'];
            unset($new_tr['title_tr'], $new_tr['description_tr'], $new_tr['organizer_tr'], $new_tr['location_tr'], $new_tr['contact_tr']);
            $events_tr[] = $new_tr;
            file_put_contents($events_tr_file, json_encode($events_tr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $save_success = true;
            redirect_home();
        }
    }

    // DELETE EVENT
    if ($action === 'delete_event') {
        $id = (int)($_POST['id'] ?? 0);
        $idx = find_event_index_by_id($events, $id);
        if ($idx !== -1) {
            array_splice($events, $idx, 1);
            save_events_to_file($events); // Write EN to events.json
            
            // Also delete from TR file
            $events_tr_file = 'eventstr.json';
            if (file_exists($events_tr_file)) {
                $events_tr = json_decode(file_get_contents($events_tr_file), true) ?? [];
                $tr_idx = find_event_index_by_id($events_tr, $id);
                if ($tr_idx !== -1) {
                    array_splice($events_tr, $tr_idx, 1);
                    file_put_contents($events_tr_file, json_encode($events_tr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
            
            $save_success = true;
        }
        redirect_home();
    }

    // UPDATE EVENT
    if ($action === 'update_event') {
        $id = (int)($_POST['id'] ?? 0);
        $idx = find_event_index_by_id($events, $id);

        if ($idx !== -1) {
            $events[$idx]['title'] = trim($_POST['title']);
            $events[$idx]['title_tr'] = trim($_POST['title_tr']);
            $events[$idx]['date'] = trim($_POST['date']);
            $events[$idx]['time'] = trim($_POST['time']);
            $events[$idx]['description'] = trim($_POST['description']);
            $events[$idx]['description_tr'] = trim($_POST['description_tr']);
            $events[$idx]['organizer'] = trim($_POST['organizer']);
            $events[$idx]['organizer_tr'] = trim($_POST['organizer']);
            
            // Handle Multi-select updates
            $events[$idx]['sdg'] = $process_multiselect('sdg');
            $events[$idx]['the_cat'] = $process_multiselect('the_cat');
            $events[$idx]['qs_cat'] = $process_multiselect('qs_cat');

            // Extra fields
            $events[$idx]['location'] = trim($_POST['location'] ?? $events[$idx]['location']);
            $events[$idx]['participants'] = trim($_POST['participants'] ?? $events[$idx]['participants']);
            $events[$idx]['status'] = trim($_POST['status'] ?? $events[$idx]['status']);
            $events[$idx]['contact'] = trim($_POST['contact'] ?? $events[$idx]['contact']);

            $img = trim($_POST['image']);
            if ($img !== '') $events[$idx]['image'] = $img;
            
            save_events_to_file($events); // Write EN to events.json
            
            // Sync TR version to eventstr.json
            $events_tr_file = 'eventstr.json';
            $events_tr = file_exists($events_tr_file) ? json_decode(file_get_contents($events_tr_file), true) ?? [] : [];
            $tr_idx = find_event_index_by_id($events_tr, $id);
            if ($tr_idx !== -1) {
                $events_tr[$tr_idx] = $events[$idx];
                $events_tr[$tr_idx]['title'] = $events[$idx]['title_tr'];
                $events_tr[$tr_idx]['description'] = $events[$idx]['description_tr'];
                $events_tr[$tr_idx]['organizer'] = $events[$idx]['organizer_tr'] ?? $events[$idx]['organizer'];
                $events_tr[$tr_idx]['location'] = $events[$idx]['location_tr'] ?? $events[$idx]['location'];
                $events_tr[$tr_idx]['contact'] = $events[$idx]['contact_tr'] ?? $events[$idx]['contact'];
                unset($events_tr[$tr_idx]['title_tr'], $events_tr[$tr_idx]['description_tr'], $events_tr[$tr_idx]['organizer_tr'], $events_tr[$tr_idx]['location_tr'], $events_tr[$tr_idx]['contact_tr']);
                file_put_contents($events_tr_file, json_encode($events_tr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            
            $save_success = true;
        }
        redirect_home();
    }
}

/* ---------- 5) KPIs & COUNTING LOGIC ---------- */
$admin_name = "Admin Staff";

// Initialize Counters
$sdg_counts = [];
$dept_counts = [];
$the_counts = 0;
$qs_counts = 0;

foreach ($events as $e) {
    // Count SDGs (Split by comma because multiple can be selected)
    if (!empty($e['sdg'])) {
        $parts = explode(',', $e['sdg']);
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p) {
                if (!isset($sdg_counts[$p])) $sdg_counts[$p] = 0;
                $sdg_counts[$p]++;
            }
        }
    }

    if (!empty($e['organizer'])) {
        $dept_counts[$e['organizer']] = true; // Unique count
    }
    
    // Just simple check if set for these stats
    if (!empty($e['the_cat'])) $the_counts++;
    if (!empty($e['qs_cat'])) $qs_counts++;
}

$total_events = count($events);
$total_unique_sdgs = count($sdg_counts);
$total_depts = count($dept_counts);

/* Edit Mode Data Prep */
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_event = null;
$current_sdgs = [];
$current_the = [];
$current_qs = [];

if ($edit_id > 0) {
    foreach ($events as $e) {
        if ((int)$e['id'] === $edit_id) { 
            $edit_event = $e; 
            // Explode comma-separated strings back to arrays for checkboxes
            $current_sdgs = isset($e['sdg']) ? array_map('trim', explode(',', $e['sdg'])) : [];
            $current_the = isset($e['the_cat']) ? array_map('trim', explode(',', $e['the_cat'])) : [];
            $current_qs = isset($e['qs_cat']) ? array_map('trim', explode(',', $e['qs_cat'])) : [];
            break; 
        }
    }
}

/* Sort events */
$sorted = $events;
usort($sorted, fn($a, $b) => strcmp($a['date'] . $a['time'], $b['date'] . $b['time']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMU | Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="images/logo.ico">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

    <style>
        /* Consistent Styling */
        :root {
            --emu-dark-blue: #002147;
            --emu-light-blue: #005A9C;
            --emu-orange: #F58220;
            --bg-body: #f4f4f4;
            --bg-card: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }

        [data-theme="dark"] {
            --emu-dark-blue: #00152e;
            --emu-light-blue: #3a86cc;
            --bg-body: #121212;
            --bg-card: #1e1e1e;
            --text-main: #e0e0e0;
            --text-muted: #aaaaaa;
            --border-color: #333333;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Open Sans', sans-serif;
            color: var(--text-main);
            transition: background-color 0.3s, color 0.3s;
        }

        h1, h2, h3, h4, h5 { font-family: 'Merriweather', serif; }

        .navbar {
            background-color: #002147;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            padding: 15px 0;
        }
        .navbar-brand img { height: 60px; }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin-left: 15px;
            font-size: 1.05rem;
        }
        .nav-link:hover { color: var(--emu-orange) !important; }

        .btn-profile {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            margin-left: 20px;
        }
        .btn-profile:hover { background-color: white; color: #002147; }

        .btn-theme-toggle {
            color: white; border: none; background: transparent;
            font-size: 1.2rem; margin-left: 10px; cursor: pointer;
        }

        .btn, .btn:focus { transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(0,0,0,0.12); }
        .card, .calendar-widget { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card:hover, .calendar-widget:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.12); }

        .hero-section {
            background: linear-gradient(rgba(0, 33, 71, 0.9), rgba(0, 33, 71, 0.8)),
                url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Eastern_Mediterranean_University_Rectorate_Building.jpg/1200px-Eastern_Mediterranean_University_Rectorate_Building.jpg');
            background-position: center; background-size: cover;
            color: white; padding: 60px 0;
            border-bottom: 5px solid var(--emu-orange);
        }

        .hero-fade { opacity: 0; transform: translateY(8px); transition: all 0.6s ease; }
        .hero-fade.show { opacity: 1; transform: translateY(0); }
        .navbar-scrolled { box-shadow: 0 4px 18px rgba(0,0,0,0.18); background-color: #001a38; transition: box-shadow 0.3s ease, background-color 0.3s ease; }

        .calendar-widget {
            background: var(--bg-card);
            border-top: 4px solid var(--emu-dark-blue);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: background-color 0.3s;
        }

        .event-card {
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
        }
        
        .sdg-tag { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; margin-right: 5px; display: inline-block; margin-bottom: 5px;}
        .criteria-tag { background-color: var(--emu-light-blue); color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; margin-right: 5px; display: inline-block; margin-bottom: 5px;}

        .text-adaptive { color: var(--text-main) !important; }
        .text-muted-adaptive { color: var(--text-muted) !important; }
        
        .table { color: var(--text-main); }
        .table td, .table th { border-top: 1px solid var(--border-color) !important; background-color: transparent !important; color: var(--text-main); }
        .table thead th { border-bottom: 2px solid var(--border-color) !important; }

        /* Multi-select Box Style */
        .multiselect-box {
            max-height: 120px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            background: var(--bg-body);
            padding: 8px;
            border-radius: 4px;
        }
        .form-check-label { font-size: 0.85rem; color: var(--text-main); }

        [data-theme="dark"] .form-control { background-color: #333; border-color: #444; color: #fff; }

        footer {
            background-color: #1a1a1a; color: #ccc;
            padding: 40px 0; border-top: 4px solid var(--emu-orange); margin-top: 60px;
        }
        html { scroll-padding-top: 100px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="https://www.emu.edu.tr/static/images/logos/emu-logo-horizontalwhite-tr.svg" alt="EMU Logo">
        </a>
        <button class="navbar-toggler navbar-dark border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" id="nav-home" href="index.php"><i class="fas fa-home me-1"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link" id="nav-manage" href="#manage"><i class="fas fa-screwdriver-wrench me-1"></i> Manage</a></li>
                <li class="nav-item"><a class="nav-link" id="nav-reports" href="#reports"><i class="fas fa-file-pdf me-1"></i> Reports</a></li>
                <li class="nav-item">
                    <a id="nav-logout" href="index.php" class="btn btn-profile btn-sm px-4 rounded-pill">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout (Admin)
                    </a>
                </li>
                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm ms-2" id="lang-toggle" type="button">TR</button>
                </li>
                <li class="nav-item">
                    <button class="btn-theme-toggle" id="theme-toggle" title="Toggle Dark/Light Mode"><i class="fas fa-moon"></i></button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-section text-center hero-fade">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3" id="hero-title">Admin Dashboard</h1>
        <p class="lead mb-0" style="opacity: 0.9;" id="hero-subtitle">Manage events, generate reports, and track SDG coverage.</p>
    </div>
</div>

<div class="container my-5">

    <?php if ($message): ?>
        <div class="alert alert-warning"><?php echo h($message); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body">
                <div class="text-muted-adaptive small mb-1" id="kpi-admin"><i class="fas fa-user-shield me-2"></i>Admin</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.2rem;"><?php echo h($admin_name); ?></div>
                <div class="text-muted-adaptive small" id="kpi-system">System Dashboard</div>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body">
                <div class="text-muted-adaptive small mb-1" id="kpi-total"><i class="fas fa-list me-2"></i>Total Events</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.8rem;"><?php echo $total_events; ?></div>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body">
                <div class="text-muted-adaptive small mb-1" id="kpi-sdgs"><i class="fas fa-globe me-2"></i>Unique SDGs</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.8rem;"><?php echo $total_unique_sdgs; ?></div>
            </div></div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body">
                <div class="text-muted-adaptive small mb-1" id="kpi-depts"><i class="fas fa-building me-2"></i>Departments</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.8rem;"><?php echo $total_depts; ?></div>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="calendar-widget p-3 mb-4 rounded-1">
                <h5 class="mb-3 text-adaptive border-bottom pb-2" id="coverage-title">Coverage Snapshot</h5>
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted-adaptive small" id="coverage-the"><i class="fas fa-university me-2"></i>Events with THE</span>
                    <span class="fw-bold text-adaptive small"><?php echo $the_counts; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted-adaptive small" id="coverage-qs"><i class="fas fa-star me-2"></i>Events with QS</span>
                    <span class="fw-bold text-adaptive small"><?php echo $qs_counts; ?></span>
                </div>
                
                <h6 class="text-adaptive mt-3 small fw-bold" id="coverage-sdg">SDG Breakdown:</h6>
                <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                    <?php if(empty($sdg_counts)): ?>
                        <small class="text-muted-adaptive">No SDGs tagged yet.</small>
                    <?php else: ?>
                        <?php foreach ($sdg_counts as $sdgName => $count): ?>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="sdg-tag mb-0" style="font-size: 0.7rem;"><?php echo h($sdgName); ?></span>
                                <span class="badge bg-secondary rounded-pill"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="calendar-widget p-3 rounded-1">
                <h5 class="mb-3 text-adaptive border-bottom pb-2" id="actions-title">Admin Actions</h5>
                <a id="action-add" class="btn btn-primary btn-sm w-100 mb-2" style="background-color: var(--emu-light-blue); border:none;" href="#manage">
                    <i class="fas fa-plus me-2"></i>Add / Edit Events
                </a>
                <a id="action-csv" class="btn btn-outline-secondary btn-sm w-100 mb-2" href="admin_dashboard.php?action=export_csv">
                    <i class="fas fa-download me-2"></i>Export CSV (Improved)
                </a>
                <button id="action-pdf" onclick="generateFullReport()" class="btn btn-outline-danger btn-sm w-100">
                    <i class="fas fa-file-pdf me-2"></i>Generate Full Report (PDF)
                </button>
            </div>
        </div>

        <div class="col-lg-8">
            <div id="manage" class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-bold m-0 text-adaptive" id="manage-title">Event Management</h3>
                <?php if ($save_success): ?>
                    <span class="text-success small" id="manage-saved"><i class="fas fa-check-circle me-1"></i>Changes saved to events</span>
                <?php endif; ?>
            </div>

            <div class="card event-card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-adaptive mb-3">
                        <?php echo $edit_event ? 'Edit Event #' . (int)$edit_event['id'] : 'Add New Event'; ?>
                    </h6>

                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $edit_event ? 'update_event' : 'add_event'; ?>">
                        <?php if ($edit_event): ?>
                            <input type="hidden" name="id" value="<?php echo (int)$edit_event['id']; ?>">
                        <?php endif; ?>

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small text-muted-adaptive">Title (EN) <span class="text-danger">*</span></label>
                                <input class="form-control" name="title" value="<?php echo h($edit_event['title'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted-adaptive">Title (TR) <span class="text-danger">*</span></label>
                                <input class="form-control" name="title_tr" value="<?php echo h($edit_event['title_tr'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted-adaptive">Date</label>
                                <input class="form-control" type="date" name="date" value="<?php echo h($edit_event['date'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted-adaptive">Time</label>
                                <input class="form-control" type="time" name="time" value="<?php echo h($edit_event['time'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-muted-adaptive">Organizer</label>
                                <input class="form-control" name="organizer" value="<?php echo h($edit_event['organizer'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted-adaptive">Image URL</label>
                                <input class="form-control" name="image" value="<?php echo h($edit_event['image'] ?? ''); ?>" placeholder="https://...">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small text-muted-adaptive">Location</label>
                                <input class="form-control" name="location" value="<?php echo h($edit_event['location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted-adaptive">Participants</label>
                                <input class="form-control" name="participants" value="<?php echo h($edit_event['participants'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted-adaptive">Status</label>
                                <select class="form-control" name="status">
                                    <option <?php echo ($edit_event['status'] ?? '') == 'Planned' ? 'selected' : ''; ?>>Planned</option>
                                    <option <?php echo ($edit_event['status'] ?? '') == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option <?php echo ($edit_event['status'] ?? '') == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted-adaptive">Contact</label>
                                <input class="form-control" name="contact" value="<?php echo h($edit_event['contact'] ?? ''); ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted-adaptive">SDGs (Multi-select)</label>
                                <div class="multiselect-box">
                                    <?php foreach($sdg_list as $item): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="sdg[]" value="<?php echo h($item); ?>" 
                                                id="chk_sdg_<?php echo md5($item); ?>"
                                                <?php echo in_array($item, $current_sdgs) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="chk_sdg_<?php echo md5($item); ?>"><?php echo h($item); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted-adaptive">THE Categories</label>
                                <div class="multiselect-box">
                                    <?php foreach($the_list as $item): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="the_cat[]" value="<?php echo h($item); ?>" 
                                                id="chk_the_<?php echo md5($item); ?>"
                                                <?php echo in_array($item, $current_the) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="chk_the_<?php echo md5($item); ?>"><?php echo h($item); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small text-muted-adaptive">QS Categories</label>
                                <div class="multiselect-box">
                                    <?php foreach($qs_list as $item): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="qs_cat[]" value="<?php echo h($item); ?>" 
                                                id="chk_qs_<?php echo md5($item); ?>"
                                                <?php echo in_array($item, $current_qs) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="chk_qs_<?php echo md5($item); ?>"><?php echo h($item); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-muted-adaptive">Description (EN) <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" rows="3" required><?php echo h($edit_event['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted-adaptive">Description (TR) <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description_tr" rows="3" required><?php echo h($edit_event['description_tr'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" style="background-color: var(--emu-light-blue); border:none;">
                                <?php echo $edit_event ? 'Update Event & Save JSON' : 'Add Event & Save'; ?>
                            </button>
                            <?php if ($edit_event): ?>
                                <a class="btn btn-outline-secondary" href="admin_dashboard.php">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div id="reports" class="card event-card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr class="text-muted-adaptive small">
                                    <th>ID</th>
                                    <th>Event</th>
                                    <th>Organizer</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sorted as $e): ?>
                                    <tr class="text-adaptive small">
                                        <td><?php echo (int)$e['id']; ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo h($e['title']); ?></div>
                                            <div class="text-muted-adaptive" style="font-size:0.75rem">
                                                <?php echo h($e['date']); ?> | <span class="text-primary"><?php echo h(substr($e['sdg'], 0, 30)) . (strlen($e['sdg'])>30?'...':''); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo h($e['organizer']); ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-outline-secondary btn-sm" href="admin_dashboard.php?edit=<?php echo (int)$e['id']; ?>#manage">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this event?');">
                                                <input type="hidden" name="action" value="delete_event">
                                                <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
                                                <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<footer>
    <div class="container text-center">
        <p class="mb-1 text-white fw-bold">Eastern Mediterranean University</p>
        <p class="small text-white mb-0">Software Engineering Course Prototype &copy; 2025 &middot; Not an official EMU service &middot; Sample data only, no real authentication</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const allEventsData = <?php echo json_encode($sorted); ?>;
    let currentLang = localStorage.getItem('lang') || 'en';
    const langToggleBtn = document.getElementById('lang-toggle');

    const translations = {
        en: {
            navHome: '<i class="fas fa-home me-1"></i> Home',
            navManage: '<i class="fas fa-screwdriver-wrench me-1"></i> Manage',
            navReports: '<i class="fas fa-file-pdf me-1"></i> Reports',
            navLogout: '<i class="fas fa-sign-out-alt me-2"></i>Logout (Admin)',
            heroTitle: 'Admin Dashboard',
            heroSubtitle: 'Manage events, generate reports, and track SDG coverage.',
            kpiAdmin: '<i class="fas fa-user-shield me-2"></i>Admin',
            kpiSystem: 'System Dashboard',
            kpiTotal: '<i class="fas fa-list me-2"></i>Total Events',
            kpiSdgs: '<i class="fas fa-globe me-2"></i>Unique SDGs',
            kpiDepts: '<i class="fas fa-building me-2"></i>Departments',
            coverageTitle: 'Coverage Snapshot',
            coverageThe: '<i class="fas fa-university me-2"></i>Events with THE',
            coverageQs: '<i class="fas fa-star me-2"></i>Events with QS',
            coverageSdg: 'SDG Breakdown:',
            actionsTitle: 'Admin Actions',
            actionAdd: '<i class="fas fa-plus me-2"></i>Add / Edit Events',
            actionCsv: '<i class="fas fa-download me-2"></i>Export CSV (Improved)',
            actionPdf: '<i class="fas fa-file-pdf me-2"></i>Generate Full Report (PDF)',
            manageTitle: 'Event Management',
            manageSaved: '<i class="fas fa-check-circle me-1"></i>Changes saved to events'
        },
        tr: {
            navHome: '<i class="fas fa-home me-1"></i> Ana Sayfa',
            navManage: '<i class="fas fa-screwdriver-wrench me-1"></i> Yönet',
            navReports: '<i class="fas fa-file-pdf me-1"></i> Raporlar',
            navLogout: '<i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap (Admin)',
            heroTitle: 'Yönetici Paneli',
            heroSubtitle: 'Etkinlikleri yönetin, raporlar oluşturun ve SKH kapsamını takip edin.',
            kpiAdmin: '<i class="fas fa-user-shield me-2"></i>Yönetici',
            kpiSystem: 'Sistem Paneli',
            kpiTotal: '<i class="fas fa-list me-2"></i>Toplam Etkinlik',
            kpiSdgs: '<i class="fas fa-globe me-2"></i>Benzersiz SKH',
            kpiDepts: '<i class="fas fa-building me-2"></i>Bölümler',
            coverageTitle: 'Kapsam Özeti',
            coverageThe: '<i class="fas fa-university me-2"></i>THE ile Etkinlikler',
            coverageQs: '<i class="fas fa-star me-2"></i>QS ile Etkinlikler',
            coverageSdg: 'SKH Dağılımı:',
            actionsTitle: 'Yönetici İşlemleri',
            actionAdd: '<i class="fas fa-plus me-2"></i>Etkinlik Ekle / Düzenle',
            actionCsv: '<i class="fas fa-download me-2"></i>CSV İndir (Geliştirilmiş)',
            actionPdf: '<i class="fas fa-file-pdf me-2"></i>Tam Rapor Oluştur (PDF)',
            manageTitle: 'Etkinlik Yönetimi',
            manageSaved: 'Değişiklikler kaydedildi'
        }
    };

    function applyTranslations(lang) {
        const dict = translations[lang] || translations.en;
        const targets = {
            'nav-home': 'navHome',
            'nav-manage': 'navManage',
            'nav-reports': 'navReports',
            'nav-logout': 'navLogout',
            'hero-title': 'heroTitle',
            'hero-subtitle': 'heroSubtitle',
            'kpi-admin': 'kpiAdmin',
            'kpi-system': 'kpiSystem',
            'kpi-total': 'kpiTotal',
            'kpi-sdgs': 'kpiSdgs',
            'kpi-depts': 'kpiDepts',
            'coverage-title': 'coverageTitle',
            'coverage-the': 'coverageThe',
            'coverage-qs': 'coverageQs',
            'coverage-sdg': 'coverageSdg',
            'actions-title': 'actionsTitle',
            'action-add': 'actionAdd',
            'action-csv': 'actionCsv',
            'action-pdf': 'actionPdf',
            'manage-title': 'manageTitle',
            'manage-saved': 'manageSaved'
        };
        Object.entries(targets).forEach(([id, key]) => {
            const el = document.getElementById(id);
            if (el && dict[key]) el.innerHTML = dict[key];
        });
        langToggleBtn.textContent = lang === 'en' ? 'TR' : 'EN';
    }

    function setLanguage(lang) {
        currentLang = lang;
        localStorage.setItem('lang', lang);
        applyTranslations(lang);
    }

    langToggleBtn.addEventListener('click', () => {
        const nextLang = currentLang === 'en' ? 'tr' : 'en';
        setLanguage(nextLang);
    });

    applyTranslations(currentLang);

    // Dark Mode
    const themeToggleBtn = document.getElementById('theme-toggle');
    const icon = themeToggleBtn.querySelector('i');
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme) {
        document.body.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark') { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
    themeToggleBtn.addEventListener('click', () => {
        let theme = document.body.getAttribute('data-theme');
        if (theme === 'dark') {
            document.body.removeAttribute('data-theme'); localStorage.setItem('theme', 'light');
            icon.classList.remove('fa-sun'); icon.classList.add('fa-moon');
        } else {
            document.body.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark');
            icon.classList.remove('fa-moon'); icon.classList.add('fa-sun');
        }
    });

    // Hero reveal and navbar shadow on scroll
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        setTimeout(() => heroSection.classList.add('show'), 150);
    }
    const navbarEl = document.querySelector('.navbar');
    const handleNavScroll = () => {
        if (!navbarEl) return;
        if (window.scrollY > 10) {
            navbarEl.classList.add('navbar-scrolled');
        } else {
            navbarEl.classList.remove('navbar-scrolled');
        }
    };
    window.addEventListener('scroll', handleNavScroll);
    handleNavScroll();

    // Admin PDF Generation
    function generateFullReport() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); // Landscape

        doc.setFillColor(0, 33, 71); doc.rect(0, 0, 297, 30, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont("helvetica", "bold"); doc.setFontSize(18);
        doc.text("EASTERN MEDITERRANEAN UNIVERSITY", 148, 12, { align: "center" });
        doc.setFontSize(10); doc.setFont("helvetica", "normal");
        doc.text("Administrative Event Report - Full Log", 148, 20, { align: "center" });

        const tableBody = allEventsData.map(e => [
            e.id, e.date, e.title, e.organizer, e.sdg, e.participants || '0', e.status || 'N/A'
        ]);

        doc.autoTable({
            startY: 40,
            head: [['ID', 'Date', 'Activity Title', 'Organizer', 'SDG Tags', 'Participants', 'Status']],
            body: tableBody,
            theme: 'grid',
            headStyles: { fillColor: [0, 90, 156] },
            styles: { fontSize: 8 },
            columnStyles: { 2: { cellWidth: 60 }, 4: { cellWidth: 50 } }
        });

        const pageCount = doc.internal.getNumberOfPages();
        doc.setFontSize(8); doc.setTextColor(100);
        for(let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.text('Confidential - Internal Use Only', 14, 200);
            doc.text(`Page ${i} of ${pageCount}`, 280, 200, { align: 'right' });
        }
        doc.save(`EMU_Admin_Report_${new Date().toISOString().slice(0,10)}.pdf`);
    }
</script>

</body>
</html>