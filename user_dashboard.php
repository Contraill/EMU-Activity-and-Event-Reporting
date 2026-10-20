<?php
require 'auth.php';
require_role(['user', 'admin']);

/**
 * EMU Activity & Event Reporting Portal
 * User Dashboard (Updated)
 * * Features: JSON Data, Session Actions, Client-side PDF Generation (Same as Index).
 */

/* ---------- 1) INITIALIZE EVENTS (Load from JSON) ---------- */
$json_file = 'events.json';
// Session'a atma zorunluluğu yok, direkt dosyadan her seferinde taze veri çekelim
// böylece index.php güncellenirse burası da güncel kalır.
if (file_exists($json_file)) {
    $events = json_decode(file_get_contents($json_file), true) ?? [];
} else {
    $events = [];
}

/* ---------- 2) USER SESSION STATE ---------- */
if (!isset($_SESSION['saved_event_ids'])) $_SESSION['saved_event_ids'] = [];
if (!isset($_SESSION['registered_event_ids'])) $_SESSION['registered_event_ids'] = [];

$saved_event_ids = &$_SESSION['saved_event_ids'];
$registered_event_ids = &$_SESSION['registered_event_ids'];

/* ---------- 3) HELPERS ---------- */
function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function redirect_home() {
    header("Location: user_dashboard.php");
    exit;
}
function has_id($arr, $id) {
    return in_array((int)$id, array_map('intval', $arr), true);
}
function remove_id(&$arr, $id) {
    $id = (int)$id;
    $arr = array_values(array_filter($arr, fn($x) => (int)$x !== $id));
}

/* ---------- 4) ACTIONS: SAVE/REGISTER ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle_save' && $id > 0) {
        if (has_id($saved_event_ids, $id)) remove_id($saved_event_ids, $id);
        else $saved_event_ids[] = $id;
        redirect_home();
    }

    if ($action === 'toggle_register' && $id > 0) {
        if (has_id($registered_event_ids, $id)) remove_id($registered_event_ids, $id);
        else $registered_event_ids[] = $id;
        redirect_home();
    }
}

/* ---------- 5) BUILD USER LISTS ---------- */
$events_by_id = [];
foreach ($events as $e) $events_by_id[(int)$e['id']] = $e;

$saved_events = [];
foreach ($saved_event_ids as $id) if (isset($events_by_id[(int)$id])) $saved_events[] = $events_by_id[(int)$id];

$registered_events = [];
foreach ($registered_event_ids as $id) if (isset($events_by_id[(int)$id])) $registered_events[] = $events_by_id[(int)$id];

// Stats
$user_name = "Student User";
$user_role = "Student";
$total_events = count($events);
$total_saved = count($saved_events);
$total_registered = count($registered_events);

// Attended events (registered + past)
$attended_count = 0;
$now = time();
foreach ($registered_events as $e) {
    $eventDateTime = strtotime($e['date'] . ' ' . $e['time']);
    if ($eventDateTime < $now) $attended_count++;
}

// Upcoming (Simple sort)
$sorted = $events;
usort($sorted, fn($a, $b) => strcmp($a['date'] . $a['time'], $b['date'] . $b['time']));
$upcoming = array_slice($sorted, 0, 3);

// Filter events for Browse section (only future events)
$now_time = time();
$browse_events = array_filter($events, function($e) use ($now_time) {
    $eventDateTime = strtotime($e['date'] . ' ' . $e['time']);
    return $eventDateTime >= $now_time;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMU | User Dashboard</title>
    <link rel="icon" type="image/x-icon" href="images/logo.ico">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

    <style>
        /* Copied from Index.php for Consistency */
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

        .event-expired .card-img-top { filter: grayscale(100%); opacity: 0.6; }
        .expired-badge { position: absolute; top: 10px; right: 10px; background-color: rgba(220, 53, 69, 0.9); color: white; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; z-index: 10; }
        .today-badge { position: absolute; top: 10px; right: 10px; background-color: rgba(245, 130, 32, 0.95); color: white; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; z-index: 10; }

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
        .event-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .sdg-tag { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; margin-right: 5px; display: inline-block; margin-bottom: 5px;}
        .criteria-tag { background-color: var(--emu-light-blue); color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; margin-right: 5px; display: inline-block; margin-bottom: 5px;}

        .text-adaptive { color: var(--text-main) !important; }
        .text-muted-adaptive { color: var(--text-muted) !important; }
        
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
                <li class="nav-item"><a class="nav-link" id="nav-events" href="#my-events"><i class="fas fa-calendar-check me-1"></i> My Events</a></li>
                <li class="nav-item"><a class="nav-link" id="nav-reports" href="#my-reports"><i class="fas fa-file-pdf me-1"></i> My Reports</a></li>

                <li class="nav-item">
                    <a id="nav-logout" href="login.php?logout=1" class="btn btn-profile btn-sm px-4 rounded-pill">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout (<?php echo h($user_role); ?>)
                    </a>
                </li>

                <li class="nav-item">
                    <button class="btn btn-outline-light btn-sm ms-2" id="lang-toggle" type="button">TR</button>
                </li>

                <li class="nav-item">
                    <button class="btn-theme-toggle" id="theme-toggle" title="Toggle Dark/Light Mode">
                        <i class="fas fa-moon"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-section text-center hero-fade">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3" id="hero-title">User Dashboard</h1>
        <p class="lead mb-0" style="opacity: 0.9;" id="hero-subtitle">Manage your saved events, registrations, and reports.</p>
    </div>
</div>

<div class="container my-5">

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body text-center">
                <div class="text-muted-adaptive small mb-1" id="kpi-user"><i class="fas fa-user me-2"></i>User</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.2rem;"><?php echo h($user_name); ?></div>
                <div class="text-muted-adaptive small"><?php echo h($user_role); ?></div>
            </div></div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body text-center">
                <div class="text-muted-adaptive small mb-1" id="kpi-registered"><i class="fas fa-calendar-check me-2"></i>Registered</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.8rem;"><?php echo $total_registered; ?></div>
                <div class="text-muted-adaptive small" id="kpi-registered-label">events</div>
            </div></div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body text-center">
                <div class="text-muted-adaptive small mb-1" id="kpi-saved"><i class="fas fa-bookmark me-2"></i>Saved</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.8rem;"><?php echo $total_saved; ?></div>
                <div class="text-muted-adaptive small" id="kpi-saved-label">events</div>
            </div></div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card event-card h-100"><div class="card-body text-center">
                <div class="text-muted-adaptive small mb-1" id="kpi-attended"><i class="fas fa-check-circle me-2"></i>Attended</div>
                <div class="fw-bold text-adaptive" style="font-size: 1.8rem;"><?php echo $attended_count; ?></div>
                <div class="text-muted-adaptive small" id="kpi-attended-label">completed events</div>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="calendar-widget p-3 mb-4 rounded-1">
                <h5 class="mb-3 text-adaptive border-bottom pb-2" id="upcoming-title">Upcoming Events</h5>
                <div id="upcoming-events-container">
                    <!-- JavaScript will populate this -->
                </div>

                <a id="browse-btn" href="index.php#all-events-section" class="btn btn-primary btn-sm w-100" style="background-color: var(--emu-light-blue); border:none;">
                    <i class="fas fa-search me-2"></i>Browse All Events (Portal)
                </a>
            </div>

            <div class="calendar-widget p-3 rounded-1">
                <h5 class="mb-3 text-adaptive border-bottom pb-2" id="tips-title">Quick Tips</h5>
                <div class="text-muted-adaptive small" id="tips-content">
                    • Save events to bookmark them<br>
                    • Register to unlock report downloads<br>
                    • Reports open as printable PDFs
                </div>
            </div>
        </div>

        <div class="col-lg-8">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fw-bold m-0 text-adaptive" id="browse-title">Browse Events</h3>
                <span class="text-muted-adaptive small" id="portal-total">Portal total: <?php echo $total_events; ?></span>
            </div>

            <div class="row" id="browse-events-container">
                <!-- JavaScript will populate this -->
            </div>

            <div id="my-reports" class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h3 class="fw-bold m-0 text-adaptive" id="reports-title">My Reports & Registrations</h3>
            </div>

            <div class="card event-card mb-4">
                <div class="card-body">
                    <?php if (count($registered_events) === 0): ?>
                        <div class="text-muted-adaptive small" id="no-registered">No registered events yet. Register to unlock reports.</div>
                    <?php else: ?>
                        <?php foreach ($registered_events as $re): ?>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2"
                                 style="border-color: var(--border-color) !important;">
                                <div>
                                    <div class="fw-bold text-adaptive small"><?php echo h($re['title']); ?></div>
                                    <div class="text-muted-adaptive small">
                                        <i class="far fa-calendar me-1"></i><?php echo date('d M Y', strtotime($re['date'])); ?> 
                                        • <?php echo h($re['sdg']); ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button onclick="generatePDF(<?php echo (int)$re['id']; ?>)" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-file-pdf me-2"></i>Download Report
                                    </button>
                                    
                                    <form method="POST" onsubmit="return confirm('Unregister from this event?');">
                                        <input type="hidden" name="action" value="toggle_register">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$re['id']; ?>">
                                        <button class="btn btn-outline-secondary btn-sm" title="Unregister">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div id="my-saved" class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h3 class="fw-bold m-0 text-adaptive" id="saved-title">Saved Events</h3>
            </div>

            <div class="card event-card mb-4">
                <div class="card-body">
                    <?php if (count($saved_events) === 0): ?>
                        <div class="text-muted-adaptive small" id="no-saved">No saved events yet.</div>
                    <?php else: ?>
                        <?php foreach ($saved_events as $se): ?>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2"
                                 style="border-color: var(--border-color) !important;">
                                <div>
                                    <div class="fw-bold text-adaptive small"><?php echo h($se['title']); ?></div>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="toggle_save">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$se['id']; ?>">
                                    <button class="btn btn-outline-secondary btn-sm">Unsave</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<footer>
    <div class="container text-center">
        <p class="mb-1 text-white fw-bold">Eastern Mediterranean University</p>
        <p class="small text-white mb-0">Software Engineering Course Prototype &copy; 2025 &middot; Not an official EMU service &middot; Sample data and public demo accounts only</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- 1. DATA TRANSFER FROM PHP TO JS ---
    let allEventsData = <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    let currentLang = localStorage.getItem('lang') || 'en';
    const langToggleBtn = document.getElementById('lang-toggle');
    const dataCache = {};

    // Fetch events based on language
    async function fetchEventsForLang(lang) {
        if (dataCache[lang]) return dataCache[lang];
        const file = lang === 'tr' ? 'eventstr.json' : 'events.json';
        try {
            const response = await fetch(file);
            const data = await response.json();
            dataCache[lang] = data;
            return data;
        } catch (error) {
            console.error('Error loading events:', error);
            return [];
        }
    }

    // Sort events by date/time
    function sortEventsBySchedule(events) {
        return events.sort((a, b) => {
            const dateA = new Date(a.date + ' ' + a.time).getTime();
            const dateB = new Date(b.date + ' ' + b.time).getTime();
            return dateA - dateB;
        });
    }

    // Render upcoming events sidebar
    function renderUpcomingSidebar(events) {
        const sorted = sortEventsBySchedule([...events]);
        const upcoming = sorted.slice(0, 3);
        const container = document.getElementById('upcoming-events-container');
        if (!container) return;
        
        container.innerHTML = '';
        upcoming.forEach(ev => {
            const dateObj = new Date(ev.date);
            const locale = currentLang === 'tr' ? 'tr-TR' : 'en-US';
            const formatted = dateObj.toLocaleDateString(locale, { day: '2-digit', month: 'short', year: 'numeric' });
            const sdgText = currentLang === 'tr' ? (sdgTranslations.tr[ev.sdg] || ev.sdg) : ev.sdg;
            
            const html = `
                <div class="mb-3">
                    <div class="fw-bold text-adaptive small">${ev.title}</div>
                    <div class="text-muted-adaptive small">
                        <i class="far fa-calendar me-1"></i>${formatted}
                        <span class="ms-2"><i class="far fa-clock me-1"></i>${ev.time}</span>
                    </div>
                    <div class="mt-2">
                        <span class="sdg-tag" data-original="${ev.sdg}">${sdgText}</span>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    // Render browse events section
    function renderBrowseEvents(events) {
        const now = Date.now();
        const browseEvents = events.filter(e => {
            const eventDateTime = new Date(e.date + ' ' + e.time).getTime();
            return eventDateTime >= now;
        });

        const container = document.getElementById('browse-events-container');
        if (!container) return;

        container.innerHTML = '';
        browseEvents.forEach(event => {
            const eventDateTime = new Date(event.date + ' ' + event.time).getTime();
            const today = new Date().setHours(0, 0, 0, 0);
            const isToday = (eventDateTime >= today && eventDateTime < today + 86400000) && eventDateTime >= now;
            const isExpired = eventDateTime < now;
            const expiredClass = isExpired ? 'event-expired' : '';

            const dateObj = new Date(event.date);
            const locale = currentLang === 'tr' ? 'tr-TR' : 'en-US';
            const formatted = dateObj.toLocaleDateString(locale, { day: '2-digit', month: 'short', year: 'numeric' });

            const dict = translations[currentLang];
            const sdgText = currentLang === 'tr' ? (sdgTranslations.tr[event.sdg] || event.sdg) : event.sdg;
            const theText = currentLang === 'tr' ? (theTranslations.tr[event.the_cat] || event.the_cat) : event.the_cat;

            const isSaved = <?php echo json_encode($saved_event_ids); ?>.includes(event.id);
            const isRegistered = <?php echo json_encode($registered_event_ids); ?>.includes(event.id);

            const badgeHtml = isToday ? `<span class="today-badge"><i class="fas fa-clock me-1"></i><span class="badge-text">${dict.todayBadge}</span></span>` :
                             isExpired ? `<span class="expired-badge"><i class="fas fa-check-circle me-1"></i><span class="badge-text">${dict.completedBadge}</span></span>` : '';

            const theTagHtml = event.the_cat ? `<span class="criteria-tag event-the" data-original="${event.the_cat}"><i class="fas fa-university me-1"></i>${theText}</span>` : '';

            const html = `
                <div class="col-md-6 mb-4 event-card-wrapper ${expiredClass}" 
                     data-date="${event.date}" 
                     data-time="${event.time}" 
                     data-sdg="${event.sdg}" 
                     data-the="${event.the_cat || ''}">
                    <div class="card event-card h-100">
                        <div style="height: 150px; overflow: hidden; background: #eee; position: relative;">
                            <img src="${event.image}" class="card-img-top w-100" style="object-fit: cover; height: 100%;" alt="Event Image">
                            ${badgeHtml}
                        </div>

                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="sdg-tag event-sdg" data-original="${event.sdg}">${sdgText}</span>
                                ${theTagHtml}
                            </div>

                            <h6 class="fw-bold mb-2 text-adaptive">${event.title}</h6>
                            <p class="text-muted-adaptive small mb-3">${event.description}</p>

                            <div class="mt-auto">
                                <div class="d-flex justify-content-between text-muted-adaptive small border-top pt-3 mb-3"
                                     style="border-color: var(--border-color) !important;">
                                    <span><i class="far fa-building me-1"></i>${event.organizer}</span>
                                    <span><i class="far fa-calendar me-1"></i>${formatted}</span>
                                </div>

                                <div class="d-flex gap-2">
                                    <form method="POST" class="w-50">
                                        <input type="hidden" name="action" value="toggle_save">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="${event.id}">
                                        <button class="btn btn-sm w-100 ${isSaved ? 'btn-secondary' : 'btn-outline-secondary'}">
                                            <i class="fas fa-bookmark me-1"></i><span class="btn-save-text">${isSaved ? dict.saved : dict.save}</span>
                                        </button>
                                    </form>

                                    <form method="POST" class="w-50">
                                        <input type="hidden" name="action" value="toggle_register">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="${event.id}">
                                        <button class="btn btn-sm w-100 ${isRegistered ? 'btn-primary' : 'btn-outline-primary'}">
                                            <i class="fas fa-calendar-check me-1"></i><span class="btn-register-text">${isRegistered ? dict.registered : dict.register}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    const translations = {
        en: {
            navHome: '<i class="fas fa-home me-1"></i> Home',
            navEvents: '<i class="fas fa-calendar-check me-1"></i> My Events',
            navReports: '<i class="fas fa-file-pdf me-1"></i> My Reports',
            navLogout: '<i class="fas fa-sign-out-alt me-2"></i>Logout (<?php echo h($user_role); ?>)',
            heroTitle: 'User Dashboard',
            heroSubtitle: 'Manage your saved events, registrations, and reports.',
            kpiUser: '<i class="fas fa-user me-2"></i>User',
            kpiRegistered: '<i class="fas fa-calendar-check me-2"></i>Registered',
            kpiRegisteredLabel: 'events',
            kpiSaved: '<i class="fas fa-bookmark me-2"></i>Saved',
            kpiSavedLabel: 'events',
            kpiAttended: '<i class="fas fa-check-circle me-2"></i>Attended',
            kpiAttendedLabel: 'completed events',
            upcomingTitle: 'Upcoming Events',
            browseBtn: '<i class="fas fa-search me-2"></i>Browse All Events (Portal)',
            tipsTitle: 'Quick Tips',
            tipsContent: '• Save events to bookmark them<br>• Register to unlock report downloads<br>• Reports open as printable PDFs',
            browseTitle: 'Browse Events',
            portalTotal: 'Portal total: <?php echo $total_events; ?>',
            reportsTitle: 'My Reports & Registrations',
            savedTitle: 'Saved Events',
            noRegistered: 'No registered events yet. Register to unlock reports.',
            noSaved: 'No saved events yet.',
            todayBadge: 'TODAY',
            completedBadge: 'COMPLETED',
            save: 'Save',
            saved: 'Saved',
            register: 'Register',
            registered: 'Registered'
        },
        tr: {
            navHome: '<i class="fas fa-home me-1"></i> Ana Sayfa',
            navEvents: '<i class="fas fa-calendar-check me-1"></i> Etkinliklerim',
            navReports: '<i class="fas fa-file-pdf me-1"></i> Raporlarım',
            navLogout: '<i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap (<?php echo h($user_role); ?>)',
            heroTitle: 'Kullanıcı Paneli',
            heroSubtitle: 'Kayıtlı etkinliklerinizi, kayıtlarınızı ve raporlarınızı yönetin.',
            kpiUser: '<i class="fas fa-user me-2"></i>Kullanıcı',
            kpiRegistered: '<i class="fas fa-calendar-check me-2"></i>Kayıtlı',
            kpiRegisteredLabel: 'etkinlik',
            kpiSaved: '<i class="fas fa-bookmark me-2"></i>Kaydedilen',
            kpiSavedLabel: 'etkinlik',
            kpiAttended: '<i class="fas fa-check-circle me-2"></i>Katıldım',
            kpiAttendedLabel: 'tamamlanan etkinlik',
            upcomingTitle: 'Yaklaşan Etkinlikler',
            browseBtn: '<i class="fas fa-search me-2"></i>Tüm Etkinliklere Göz At (Portal)',
            tipsTitle: 'Hızlı İpuçları',
            tipsContent: '• Etkinlikleri yer imi olarak kaydedin<br>• Rapor indirmek için kaydolun<br>• Raporlar yazdırılabilir PDF olarak açılır',
            browseTitle: 'Etkinliklere Göz At',
            portalTotal: 'Portal toplamı: <?php echo $total_events; ?>',
            reportsTitle: 'Raporlarım ve Kayıtlarım',
            savedTitle: 'Kayıtlı Etkinlikler',
            noRegistered: 'Henüz kayıtlı etkinlik yok. Rapor indirmek için kaydolun.',
            noSaved: 'Henüz kayıtlı etkinlik yok.',
            todayBadge: 'BUGÜN',
            completedBadge: 'TAMAMLANDI',
            save: 'Kaydet',
            saved: 'Kaydedildi',
            register: 'Kaydol',
            registered: 'Kayıtlı'
        }
    };

    const sdgTranslations = {
        tr: {
            'SDG 1: No Poverty': 'SKH 1: Yoksulluğa Son',
            'SDG 2: Zero Hunger': 'SKH 2: Açlığa Son',
            'SDG 3: Good Health and Well-being': 'SKH 3: Sağlık ve Kaliteli Yaşam',
            'SDG 4: Quality Education': 'SKH 4: Nitelikli Eğitim',
            'SDG 5: Gender Equality': 'SKH 5: Toplumsal Cinsiyet Eşitliği',
            'SDG 6: Clean Water and Sanitation': 'SKH 6: Temiz Su ve Sanitasyon',
            'SDG 7: Affordable and Clean Energy': 'SKH 7: Erişilebilir ve Temiz Enerji',
            'SDG 8: Decent Work and Economic Growth': 'SKH 8: İnsana Yakışır İş ve Ekonomik Büyüme',
            'SDG 9: Industry, Innovation and Infrastructure': 'SKH 9: Sanayi, Yenilikçilik ve Altyapı',
            'SDG 10: Reduced Inequalities': 'SKH 10: Eşitsizliklerin Azaltılması',
            'SDG 11: Sustainable Cities and Communities': 'SKH 11: Sürdürülebilir Şehirler ve Topluluklar',
            'SDG 12: Responsible Consumption and Production': 'SKH 12: Sorumlu Üretim ve Tüketim',
            'SDG 13: Climate Action': 'SKH 13: İklim Eylemi',
            'SDG 14: Life Below Water': 'SKH 14: Suda Yaşam',
            'SDG 15: Life on Land': 'SKH 15: Karasal Yaşam',
            'SDG 16: Peace, Justice and Strong Institutions': 'SKH 16: Barış, Adalet ve Güçlü Kurumlar',
            'SDG 17: Partnerships for the Goals': 'SKH 17: Amaçlar İçin Ortaklıklar'
        }
    };

    const theTranslations = {
        tr: {
            'Teaching': 'Öğretim',
            'Research': 'Araştırma',
            'Citations Impact': 'Atıf Etkisi',
            'International Outlook': 'Uluslararası Görünüm',
            'Industry Income / Collaboration': 'Sektör Geliri / İşbirliği'
        }
    };

    function applyTranslations(lang) {
        const dict = translations[lang] || translations.en;
        const targets = {
            'nav-home': 'navHome',
            'nav-events': 'navEvents',
            'nav-reports': 'navReports',
            'nav-logout': 'navLogout',
            'hero-title': 'heroTitle',
            'hero-subtitle': 'heroSubtitle',
            'kpi-user': 'kpiUser',
            'kpi-registered': 'kpiRegistered',
            'kpi-registered-label': 'kpiRegisteredLabel',
            'kpi-saved': 'kpiSaved',
            'kpi-saved-label': 'kpiSavedLabel',
            'kpi-attended': 'kpiAttended',
            'kpi-attended-label': 'kpiAttendedLabel',
            'upcoming-title': 'upcomingTitle',
            'browse-btn': 'browseBtn',
            'tips-title': 'tipsTitle',
            'tips-content': 'tipsContent',
            'browse-title': 'browseTitle',
            'portal-total': 'portalTotal',
            'reports-title': 'reportsTitle',
            'saved-title': 'savedTitle',
            'no-registered': 'noRegistered',
            'no-saved': 'noSaved'
        };
        Object.entries(targets).forEach(([id, key]) => {
            const el = document.getElementById(id);
            if (el && dict[key]) el.innerHTML = dict[key];
        });

        // Translate SDG/THE tags
        document.querySelectorAll('.event-sdg').forEach(tag => {
            const original = tag.getAttribute('data-original') || tag.textContent;
            if (!tag.getAttribute('data-original')) tag.setAttribute('data-original', original);
            tag.textContent = lang === 'tr' ? (sdgTranslations.tr[original] || original) : original;
        });
        document.querySelectorAll('.event-the').forEach(tag => {
            const iconHtml = '<i class="fas fa-university me-1"></i>';
            const textNode = tag.childNodes[tag.childNodes.length - 1];
            const original = tag.getAttribute('data-original') || textNode.textContent;
            if (!tag.getAttribute('data-original')) tag.setAttribute('data-original', original);
            const translated = lang === 'tr' ? (theTranslations.tr[original] || original) : original;
            tag.innerHTML = iconHtml + translated;
        });

        // Translate badges
        document.querySelectorAll('.today-badge .badge-text').forEach(el => {
            el.textContent = dict.todayBadge;
        });
        document.querySelectorAll('.expired-badge .badge-text').forEach(el => {
            el.textContent = dict.completedBadge;
        });

        // Translate buttons
        document.querySelectorAll('.btn-save-text').forEach(btn => {
            const isSaved = btn.parentElement.classList.contains('btn-secondary');
            btn.textContent = isSaved ? dict.saved : dict.save;
        });
        document.querySelectorAll('.btn-register-text').forEach(btn => {
            const isRegistered = btn.parentElement.classList.contains('btn-primary');
            btn.textContent = isRegistered ? dict.registered : dict.register;
        });

        langToggleBtn.textContent = lang === 'en' ? 'TR' : 'EN';
    }

    async function setLanguage(lang) {
        currentLang = lang;
        localStorage.setItem('lang', lang);
        applyTranslations(lang);
        
        // Fetch and render events for the selected language
        const events = await fetchEventsForLang(lang);
        allEventsData = events;
        renderUpcomingSidebar(events);
        renderBrowseEvents(events);
    }

    langToggleBtn.addEventListener('click', async () => {
        const nextLang = currentLang === 'en' ? 'tr' : 'en';
        await setLanguage(nextLang);
    });

    // Initialize with cached or default language
    (async () => {
        await setLanguage(currentLang);
    })();

    // --- 2. DARK MODE ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const icon = themeToggleBtn.querySelector('i');

    const currentTheme = localStorage.getItem('theme');
    if (currentTheme) {
        document.body.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }

    themeToggleBtn.addEventListener('click', () => {
        let theme = document.body.getAttribute('data-theme');
        if (theme === 'dark') {
            document.body.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        } else {
            document.body.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    });

    // Hero reveal and navbar scroll shadow
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

    // --- 3. DETAILED PDF GENERATION (Copied from Index.php) ---
    function generatePDF(eventId) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        const event = allEventsData.find(e => e.id == eventId);
        if (!event) return;

        // HEADER (Blue Box)
        doc.setFillColor(0, 33, 71); 
        doc.rect(0, 0, 210, 40, 'F');
        
        doc.setTextColor(255, 255, 255);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(22);
        doc.text("EASTERN MEDITERRANEAN UNIVERSITY", 105, 20, { align: "center" });
        
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        doc.text("Activity & Event Reporting System", 105, 30, { align: "center" });

        doc.setDrawColor(245, 130, 32); 
        doc.setLineWidth(1.5);
        doc.line(0, 41, 210, 41);

        // METADATA
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(10);
        doc.text(`Report ID: EMU-${event.id}-${new Date().getFullYear()}`, 150, 55);
        doc.text(`Activity Date: ${event.date}`, 150, 60);

        // TITLE
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(0, 33, 71);
        doc.text("OFFICIAL ACTIVITY REPORT", 14, 60);
        doc.setDrawColor(200, 200, 200);
        doc.line(14, 65, 196, 65);

        // DETAILED TABLE
        doc.autoTable({
            startY: 75,
            head: [['Attribute', 'Details']],
            body: [
                ['Activity Title', event.title],
                ['Organizer', event.organizer],
                ['Date & Time', `${event.date} at ${event.time}`],
                ['Location', event.location || 'N/A'], 
                ['Participant Count', event.participants || 'N/A'], 
                ['Event Status', event.status || 'N/A'], 
                ['Contact Person', event.contact || 'N/A'], 
                ['SDG Alignment', event.sdg],
                ['THE Ranking Category', event.the_cat || 'N/A'],
                ['QS Stars Category', event.qs_cat || 'N/A']
            ],
            theme: 'grid',
            headStyles: { fillColor: [0, 90, 156] }, 
            columnStyles: { 0: { fontStyle: 'bold', cellWidth: 50 } }
        });

        // DESCRIPTION
        let finalY = doc.lastAutoTable.finalY + 15;
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(0, 33, 71);
        doc.text("Activity Description / Impact Analysis", 14, finalY);
        
        doc.setFontSize(11);
        doc.setFont("helvetica", "normal");
        doc.setTextColor(50, 50, 50);
        
        const descLines = doc.splitTextToSize(event.description, 180);
        doc.text(descLines, 14, finalY + 8);

        // SIGN OFF
        const pageHeight = doc.internal.pageSize.height;
        doc.setDrawColor(245, 130, 32);
        doc.line(14, pageHeight - 30, 196, pageHeight - 30);
        
        doc.setFontSize(9);
        doc.setTextColor(100, 100, 100);
        doc.text("This document is automatically generated by the EMU Activity Reporting System.", 14, pageHeight - 20);
        doc.text("Accreditation & Quality Assurance Office", 14, pageHeight - 15);
        
        doc.save(`EMU_Report_${event.date}_${event.id}.pdf`);
    }
</script>

</body>
</html>