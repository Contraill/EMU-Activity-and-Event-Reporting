<?php
/**
 * EMU Activity & Event Reporting Portal
 * Prototype v10
 * * Features: Login Modal, Role-Based Redirects (Admin/User), PDF Generation.
 */

require 'auth.php';

// --- DATA SOURCE CONFIGURATION ---
$json_file = 'events.json';
$events = [];

// Load JSON
if (file_exists($json_file)) {
    $events = json_decode(file_get_contents($json_file), true) ?? [];
    // Sort events: TODAY first, then upcoming, then past events
    usort($events, function($a, $b) {
        $dateA = strtotime($a['date']);
        $dateB = strtotime($b['date']);
        $timeA = strtotime($a['date'] . ' ' . $a['time']);
        $timeB = strtotime($b['date'] . ' ' . $b['time']);
        $now = time();
        $today = strtotime(date('Y-m-d'));
        
        $isTodayA = ($dateA >= $today && $dateA < $today + 86400) && $timeA >= $now;
        $isTodayB = ($dateB >= $today && $dateB < $today + 86400) && $timeB >= $now;
        $isExpiredA = $timeA < $now;
        $isExpiredB = $timeB < $now;
        
        // TODAY events first
        if ($isTodayA && !$isTodayB) return -1;
        if (!$isTodayA && $isTodayB) return 1;
        
        // If both today, sort by time
        if ($isTodayA && $isTodayB) return $timeA - $timeB;
        
        // Then upcoming events
        if (!$isExpiredA && !$isExpiredB) return $dateA - $dateB;
        
        // Then past events
        if ($isExpiredA && $isExpiredB) return $dateB - $dateA;
        
        // Upcoming before past
        return $isExpiredA ? 1 : -1;
    });
}

// Filter Lists
$sdgs = [
    "SDG 1: No Poverty", "SDG 2: Zero Hunger", "SDG 3: Good Health and Well-being",
    "SDG 4: Quality Education", "SDG 5: Gender Equality", "SDG 6: Clean Water and Sanitation",
    "SDG 7: Affordable and Clean Energy", "SDG 8: Decent Work and Economic Growth",
    "SDG 9: Industry, Innovation and Infrastructure", "SDG 10: Reduced Inequalities",
    "SDG 11: Sustainable Cities and Communities", "SDG 12: Responsible Consumption and Production",
    "SDG 13: Climate Action", "SDG 14: Life Below Water", "SDG 15: Life on Land",
    "SDG 16: Peace, Justice and Strong Institutions", "SDG 17: Partnerships for the Goals"
];

$the_criteria = ["Teaching", "Research", "Citations Impact", "International Outlook", "Industry Income / Collaboration"];
$qs_criteria = ["Teaching & Learning", "Employability", "Research Quality", "Internationalization", "Inclusiveness", "Social Responsibility"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMU | Activity & Event Reporting Portal</title>
    <link rel="icon" type="image/x-icon" href="images/logo.ico">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

    <style>
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
        .btn-login {
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            margin-left: 20px;
        }
        .btn-login:hover { background-color: white; color: #002147; }
        .btn-theme-toggle {
            color: white;
            border: none;
            background: transparent;
            font-size: 1.2rem;
            margin-left: 10px;
            cursor: pointer;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 33, 71, 0.9), rgba(0, 33, 71, 0.8)), url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Eastern_Mediterranean_University_Rectorate_Building.jpg/1200px-Eastern_Mediterranean_University_Rectorate_Building.jpg');
            background-position: center;
            background-size: cover;
            color: white;
            padding: 60px 0;
            border-bottom: 5px solid var(--emu-orange);
        }

        /* UI Components */
        .calendar-widget {
            background: var(--bg-card);
            border-top: 4px solid var(--emu-dark-blue);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: background-color 0.3s;
        }
        .date-box {
            background-color: var(--emu-dark-blue);
            color: white;
            width: 50px;
            text-align: center;
            border-radius: 4px;
            padding: 5px 0;
            margin-right: 15px;
        }
        .date-box .month { font-size: 0.7rem; text-transform: uppercase; display: block; }
        .date-box .day { font-size: 1.2rem; font-weight: bold; line-height: 1; }

        .event-card {
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .sdg-tag { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; margin-right: 5px; display: inline-block; margin-bottom: 5px;}
        .criteria-tag { background-color: var(--emu-light-blue); color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; margin-right: 5px; display: inline-block; margin-bottom: 5px;}

        .filter-box-container {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        .scrollable-checkboxes {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            padding: 10px;
            border-radius: 4px;
            background-color: var(--bg-body);
        }
        .scrollable-checkboxes::-webkit-scrollbar { width: 6px; }
        .scrollable-checkboxes::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 4px; }

        .text-adaptive { color: var(--text-main) !important; }
        .text-muted-adaptive { color: var(--text-muted) !important; }
        
        [data-theme="dark"] .text-dark { color: #fff !important; }
        [data-theme="dark"] .bg-white { background-color: #1e1e1e !important; }
        [data-theme="dark"] .border-bottom { border-color: #444 !important; }
        [data-theme="dark"] .form-control { background-color: #333; border-color: #444; color: #fff; }
        [data-theme="dark"] .modal-content { background-color: #1e1e1e; color: #e0e0e0; }
        [data-theme="dark"] .modal-body { background-color: #1e1e1e; }
        [data-theme="dark"] .input-group-text { background-color: #333; border-color: #444; color: #aaa; }
        [data-theme="dark"] .text-muted { color: #aaa !important; }

        .event-expired .card-img-top { filter: grayscale(100%); opacity: 0.6; }
        .expired-badge { position: absolute; top: 10px; right: 10px; background-color: rgba(220, 53, 69, 0.9); color: white; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; z-index: 10; }
        .today-badge { position: absolute; top: 10px; right: 10px; background-color: rgba(245, 130, 32, 0.95); color: white; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; z-index: 10; }
        
        .event-counter { font-weight: bold; color: var(--emu-light-blue); margin-left: 10px; }
        
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .counter-animate { animation: countUp 1.5s ease-out; }
        .hero-fade { opacity: 0; transform: translateY(8px); transition: all 0.6s ease; }
        .hero-fade.show { opacity: 1; transform: translateY(0); }
        .navbar-scrolled { box-shadow: 0 4px 18px rgba(0,0,0,0.18); background-color: #001a38; transition: box-shadow 0.3s ease, background-color 0.3s ease; }
        .btn, .btn:focus { transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(0,0,0,0.12); }
        .card, .filter-box-container, .calendar-widget { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card:hover, .filter-box-container:hover, .calendar-widget:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.12); }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .event-item-lazy {
            animation: slideInUp 0.6s ease-out forwards;
        }

        footer {
            background-color: #1a1a1a;
            color: #ccc;
            padding: 40px 0;
            margin-top: 60px;
            border-top: 4px solid var(--emu-orange);
        }
        
        html { scroll-padding-top: 100px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="https://www.emu.edu.tr/static/images/logos/emu-logo-horizontalwhite-tr.svg" alt="EMU Logo">
            </a>
            
            <button class="navbar-toggler navbar-dark border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" id="nav-calendar" href="calendar.php"><i class="far fa-calendar-alt me-1"></i> Calendar</a></li>
                    <li class="nav-item"><a class="nav-link" id="nav-events" href="#all-events-section"><i class="fas fa-list me-1"></i> Events</a></li>
                    <li class="nav-item">
                        <a id="nav-login" href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="btn btn-login btn-sm px-4 rounded-pill">
                            <i class="fas fa-lock me-2"></i>Demo Login
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
            <h1 class="display-5 fw-bold mb-3" id="hero-title">Activity & Event Reporting</h1>
            <p class="lead mb-0" style="opacity: 0.9;" id="hero-subtitle">
                Track university achievements, SDG contributions, and accreditation standards.
            </p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            
            <div class="col-lg-3 mb-4">
                <div id="calendar" class="calendar-widget p-3 mb-4 rounded-1">
                    <h5 class="mb-3 text-adaptive border-bottom pb-2" id="upcoming-heading">Upcoming Calendar</h5>
                    <div id="calendar-list">
                        <?php if(empty($events)): ?>
                            <small class="text-muted">No events loaded.</small>
                        <?php else: ?>
                            <?php foreach(array_slice($events, 0, 3) as $ev): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="date-box flex-shrink-0">
                                    <span class="month"><?php echo date('M', strtotime($ev['date'])); ?></span>
                                    <span class="day"><?php echo date('d', strtotime($ev['date'])); ?></span>
                                </div>
                                <div class="ms-2">
                                    <small class="d-block fw-bold text-adaptive text-truncate" style="max-width: 150px;">
                                        <?php echo $ev['title']; ?>
                                    </small>
                                    <small class="text-muted-adaptive" style="font-size: 0.75rem;">
                                        <i class="far fa-clock"></i> <?php echo $ev['time']; ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="filter-box-container p-3 shadow-sm rounded-1">
                    <h5 class="mb-3 text-adaptive" id="filters-title"><i class="fas fa-filter me-2"></i>Filters</h5>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted-adaptive fw-bold" id="search-label">Search</label>
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Title, keyword..." data-i18n-placeholder="searchPlaceholder">
                            <button class="btn btn-outline-secondary" type="button" id="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted-adaptive fw-bold" id="sdg-label">UN Sustainable Goals</label>
                        <div class="scrollable-checkboxes">
                            <?php foreach($sdgs as $index => $sdg): ?>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="sdg" value="<?php echo $sdg; ?>" id="sdgCheck<?php echo $index; ?>">
                                <label class="form-check-label small text-adaptive" for="sdgCheck<?php echo $index; ?>">
                                    <?php echo $sdg; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted-adaptive fw-bold" id="the-label">THE Impact Ranking</label>
                        <div class="scrollable-checkboxes" style="max-height: 100px;">
                            <?php foreach($the_criteria as $index => $crit): ?>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="the" value="<?php echo $crit; ?>" id="theCheck<?php echo $index; ?>">
                                <label class="form-check-label small text-adaptive" for="theCheck<?php echo $index; ?>">
                                    <?php echo $crit; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                            <div class="mb-3">
                                <label class="form-label small text-muted-adaptive fw-bold" id="qs-label">QS Stars Evaluation</label>
                        <div class="scrollable-checkboxes" style="max-height: 100px;">
                            <?php foreach($qs_criteria as $index => $crit): ?>
                            <div class="form-check">
                                <input class="form-check-input filter-checkbox" type="checkbox" name="qs" value="<?php echo $crit; ?>" id="qsCheck<?php echo $index; ?>">
                                <label class="form-check-label small text-adaptive" for="qsCheck<?php echo $index; ?>">
                                    <?php echo $crit; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hideExpiredCheck">
                            <label class="form-check-label small text-adaptive fw-bold" for="hideExpiredCheck" id="hide-label">
                                <i class="fas fa-eye-slash me-1"></i>Hide Past Events
                            </label>
                        </div>
                    </div>

                    <button onclick="location.reload()" class="btn btn-outline-secondary w-100 btn-sm text-adaptive" id="reset-btn">
                        Reset All
                    </button>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4" id="all-events-section">
                    <div class="d-flex align-items-center">
                        <h3 class="fw-bold m-0 text-adaptive" id="all-events-title">All Events</h3>
                        <span class="event-counter counter-animate" id="event-counter">0</span>
                    </div>
                </div>

                <div class="row" id="events-container">
                </div>
                
                <div id="no-results" class="alert alert-warning text-center" style="display:none;" data-i18n="noResults">
                    No events found matching your search or filters.
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

    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--emu-dark-blue); color: white;">
                    <h5 class="modal-title" id="login-modal-title"><i class="fas fa-user-shield me-2"></i>Demo Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="modal-body p-4" method="POST" action="login.php">
                    <?php echo csrf_field(); ?>
                    <p class="text-muted small mb-3" id="login-desc">Demo login for this course prototype (admin/admin, user/user). Credentials are checked on the server, but they are public demo accounts.</p>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold small" id="username-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" autocomplete="username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold small" id="password-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" placeholder="******" required>
                        </div>
                    </div>
                    
                    <div id="login-error" class="alert alert-danger py-2 small" style="display:none;">
                        <i class="fas fa-exclamation-circle me-1"></i> <span id="login-error-text">Invalid username or password.</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" style="background-color: var(--emu-orange); border:none;" id="login-btn">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // --- 1. DATA TRANSFER FROM PHP TO JS ---
        let allEventsData = <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        let totalEventCount = allEventsData.length; // Store total count at page load
        const dataCache = { en: allEventsData, tr: null };

        // Keep ordering consistent with PHP sorting: today (upcoming) first, then upcoming, then past
        function sortEventsBySchedule(events) {
            const now = Date.now();
            const todayStart = new Date(new Date().toDateString()).getTime();
            const todayEnd = todayStart + 86400000;
            return [...events].sort((a, b) => {
                const dateA = new Date(`${a.date} ${a.time}`).getTime();
                const dateB = new Date(`${b.date} ${b.time}`).getTime();
                const dayA = new Date(a.date).setHours(0, 0, 0, 0);
                const dayB = new Date(b.date).setHours(0, 0, 0, 0);

                const isTodayA = dayA >= todayStart && dayA < todayEnd && dateA >= now;
                const isTodayB = dayB >= todayStart && dayB < todayEnd && dateB >= now;
                const isExpiredA = dateA < now;
                const isExpiredB = dateB < now;

                if (isTodayA && !isTodayB) return -1;
                if (!isTodayA && isTodayB) return 1;
                if (isTodayA && isTodayB) return dateA - dateB;
                if (!isExpiredA && !isExpiredB) return dayA - dayB;
                if (isExpiredA && isExpiredB) return dayB - dayA;
                return isExpiredA ? 1 : -1;
            });
        }
        const translations = {
            en: {
                navCalendar: '<i class="far fa-calendar-alt me-1"></i> Calendar',
                navEvents: '<i class="fas fa-list me-1"></i> Events',
                navLogin: '<i class="fas fa-lock me-2"></i>Demo Login',
                heroTitle: 'Activity & Event Reporting',
                heroSubtitle: 'Track university achievements, SDG contributions, and accreditation standards.',
                upcomingHeading: 'Upcoming Calendar',
                filtersTitle: '<i class="fas fa-filter me-2"></i>Filters',
                searchLabel: 'Search',
                searchPlaceholder: 'Title, keyword...',
                sdgLabel: 'UN Sustainable Goals',
                theLabel: 'THE Impact Ranking',
                qsLabel: 'QS Stars Evaluation',
                hideLabel: '<i class="fas fa-eye-slash me-1"></i>Hide Past Events',
                resetBtn: 'Reset All',
                allEventsTitle: 'All Events',
                noResults: 'No events found matching your search or filters.',
                loginModalTitle: '<i class="fas fa-user-shield me-2"></i>Demo Login',
                loginDesc: 'Demo login for this course prototype (admin/admin, user/user). Credentials are checked on the server, but they are public demo accounts.',
                usernameLabel: 'Username',
                passwordLabel: 'Password',
                loginError: 'Invalid username or password.',
                loginBtn: 'Login',
                todayBadge: 'TODAY',
                completedBadge: 'COMPLETED',
                downloadReport: 'Download Report',
                dateAt: 'at',
                noEventsLoaded: 'No events loaded.'
            },
            tr: {
                navCalendar: '<i class="far fa-calendar-alt me-1"></i> Takvim',
                navEvents: '<i class="fas fa-list me-1"></i> Etkinlikler',
                navLogin: '<i class="fas fa-lock me-2"></i>Demo Giriş',
                heroTitle: 'Etkinlik ve Faaliyet Raporlama',
                heroSubtitle: 'Üniversite başarılarını, Sürdürülebilirlik hedeflerini ve akreditasyon standartlarını takip edin.',
                upcomingHeading: 'Yaklaşan Takvim',
                filtersTitle: '<i class="fas fa-filter me-2"></i>Filtreler',
                searchLabel: 'Arama',
                searchPlaceholder: 'Başlık, anahtar kelime...',
                sdgLabel: 'BM Sürdürülebilir Kalkınma Hedefleri',
                theLabel: 'THE Etki Sıralaması',
                qsLabel: 'QS Stars Değerlendirme',
                hideLabel: '<i class="fas fa-eye-slash me-1"></i>Geçmiş Etkinlikleri Gizle',
                resetBtn: 'Tümünü Sıfırla',
                allEventsTitle: 'Tüm Etkinlikler',
                noResults: 'Arama veya filtrelerle eşleşen etkinlik bulunamadı.',
                loginModalTitle: '<i class="fas fa-user-shield me-2"></i>Demo Giriş',
                loginDesc: 'Ders prototipi için demo giriş (admin/admin, user/user). Bilgiler sunucuda kontrol edilir ama herkese açık demo hesaplardır.',
                usernameLabel: 'Kullanıcı Adı',
                passwordLabel: 'Şifre',
                loginError: 'Geçersiz kullanıcı adı veya şifre.',
                loginBtn: 'Giriş Yap',
                todayBadge: 'BUGÜN',
                completedBadge: 'TAMAMLANDI',
                downloadReport: 'Raporu İndir',
                dateAt: 'saat',
                noEventsLoaded: 'Etkinlik bulunamadı.'
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
        const qsTranslations = {
            tr: {
                'Teaching & Learning': 'Öğretim ve Öğrenme',
                'Employability': 'İstihdam Edilebilirlik',
                'Research Quality': 'Araştırma Kalitesi',
                'Internationalization': 'Uluslararasılaşma',
                'Inclusiveness': 'Kapsayıcılık',
                'Social Responsibility': 'Sosyal Sorumluluk'
            }
        };
        const getTranslatedSdg = (val, lang) => lang === 'tr' ? (sdgTranslations.tr[val] || val) : val;
        const getTranslatedThe = (val, lang) => lang === 'tr' ? (theTranslations.tr[val] || val) : val;
        const getTranslatedQs = (val, lang) => lang === 'tr' ? (qsTranslations.tr[val] || val) : val;
        allEventsData = sortEventsBySchedule(allEventsData);
        dataCache.en = allEventsData;
        totalEventCount = allEventsData.length;

        function fetchEventsForLang(lang) {
            if (dataCache[lang]) return Promise.resolve(dataCache[lang]);
            const file = lang === 'tr' ? 'eventstr.json' : 'events.json';
            return fetch(file)
                .then(resp => resp.ok ? resp.json() : [])
                .then(data => {
                    const safeData = Array.isArray(data) ? data : [];
                    const sorted = sortEventsBySchedule(safeData);
                    dataCache[lang] = sorted;
                    return sorted;
                })
                .catch(() => dataCache[lang] || []);
        }
        const translationTargets = {
            'nav-calendar': 'navCalendar',
            'nav-events': 'navEvents',
            'nav-login': 'navLogin',
            'hero-title': 'heroTitle',
            'hero-subtitle': 'heroSubtitle',
            'upcoming-heading': 'upcomingHeading',
            'filters-title': 'filtersTitle',
            'search-label': 'searchLabel',
            'sdg-label': 'sdgLabel',
            'the-label': 'theLabel',
            'qs-label': 'qsLabel',
            'hide-label': 'hideLabel',
            'reset-btn': 'resetBtn',
            'all-events-title': 'allEventsTitle',
            'no-results': 'noResults',
            'login-modal-title': 'loginModalTitle',
            'login-desc': 'loginDesc',
            'username-label': 'usernameLabel',
            'password-label': 'passwordLabel',
            'login-error-text': 'loginError',
            'login-btn': 'loginBtn'
        };
        const placeholderTargets = {
            'search-input': 'searchPlaceholder'
        };
        let currentLang = localStorage.getItem('lang') || 'en';
        
        // Lazy loading variables
        let currentLoadIndex = 0;
        const eventsPerLoad = 6;
        let isLoadingMore = false;

        // --- 2. DARK MODE ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const icon = themeToggleBtn.querySelector('i');
        const langToggleBtn = document.getElementById('lang-toggle');
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

        function applyTranslations(lang) {
            const dict = translations[lang] || translations.en;
            Object.entries(translationTargets).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el && dict[key]) { el.innerHTML = dict[key]; }
            });
            Object.entries(placeholderTargets).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el && dict[key]) { el.placeholder = dict[key]; }
            });
            // Translate filter checkbox labels without changing their values
            document.querySelectorAll('input[name="sdg"]').forEach(cb => {
                const labelEl = cb.nextElementSibling;
                if (labelEl) labelEl.textContent = (sdgTranslations[lang] || {})[cb.value] || cb.value;
            });
            document.querySelectorAll('input[name="the"]').forEach(cb => {
                const labelEl = cb.nextElementSibling;
                if (labelEl) labelEl.textContent = (theTranslations[lang] || {})[cb.value] || cb.value;
            });
            document.querySelectorAll('input[name="qs"]').forEach(cb => {
                const labelEl = cb.nextElementSibling;
                if (labelEl) labelEl.textContent = (qsTranslations[lang] || {})[cb.value] || cb.value;
            });
            langToggleBtn.textContent = lang === 'en' ? 'TR' : 'EN';
        }

        function setLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('lang', lang);
            fetchEventsForLang(lang)
                .then(data => {
                    allEventsData = data;
                    totalEventCount = allEventsData.length;
                    applyTranslations(lang);
                    rerenderEventsForLanguage();
                    renderCalendarSidebar(allEventsData, lang);
                })
                .catch(() => {
                    applyTranslations(lang);
                    rerenderEventsForLanguage();
                    renderCalendarSidebar(allEventsData, lang);
                });
        }
        langToggleBtn.addEventListener('click', () => {
            const nextLang = currentLang === 'en' ? 'tr' : 'en';
            setLanguage(nextLang);
        });
        applyTranslations(currentLang);

        // Subtle hero reveal and navbar scroll style
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

        function renderCalendarSidebar(events, lang) {
            const listEl = document.getElementById('calendar-list');
            if (!listEl) return;
            const dict = translations[lang] || translations.en;

            if (!events || events.length === 0) {
                listEl.innerHTML = `<small class="text-muted">${dict.noEventsLoaded}</small>`;
                return;
            }

            const locale = lang === 'tr' ? 'tr-TR' : 'en-US';
            const topEvents = events.slice(0, 3);
            const markup = topEvents.map(ev => {
                const d = new Date(ev.date);
                const month = d.toLocaleString(locale, { month: 'short' });
                const day = d.toLocaleString(locale, { day: '2-digit' });
                return `
                    <div class="d-flex align-items-center mb-3">
                        <div class="date-box flex-shrink-0">
                            <span class="month">${month}</span>
                            <span class="day">${day}</span>
                        </div>
                        <div class="ms-2">
                            <small class="d-block fw-bold text-adaptive text-truncate" style="max-width: 150px;">
                                ${ev.title}
                            </small>
                            <small class="text-muted-adaptive" style="font-size: 0.75rem;">
                                <i class="far fa-clock"></i> ${ev.time}
                            </small>
                        </div>
                    </div>
                `;
            }).join('');
            listEl.innerHTML = markup;
        }

        // --- 3. LAZY LOADING FUNCTION ---
        function loadMoreEvents() {
            if (isLoadingMore) return;
            isLoadingMore = true;
            
            const container = document.getElementById('events-container');
            const endIndex = Math.min(currentLoadIndex + eventsPerLoad, allEventsData.length);
            const dict = translations[currentLang] || translations.en;
            const locale = currentLang === 'tr' ? 'tr-TR' : 'en-US';
            
            for (let i = currentLoadIndex; i < endIndex; i++) {
                const event = allEventsData[i];
                const eventDateTime = new Date(event['date'] + ' ' + event['time']).getTime();
                const now = Date.now();
                const today = new Date(new Date().toDateString()).getTime();
                const isToday = eventDateTime >= today && eventDateTime < today + 86400000 && eventDateTime >= now;
                const isExpired = eventDateTime < now;
                const expiredClass = isExpired ? 'event-expired' : '';
                
                 const eventHTML = `
                <div class="col-md-6 mb-4 event-item event-item-lazy ${expiredClass}" 
                     data-sdg="${event['sdg']}" 
                     data-the="${event['the_cat'] ?? ''}" 
                     data-qs="${event['qs_cat'] ?? ''}"
                     data-expired="${isExpired ? '1' : '0'}"
                     data-today="${isToday ? '1' : '0'}">
                     
                    <div class="card event-card h-100">
                        <div style="height: 160px; overflow: hidden; background: #eee; position: relative;">
                            <img src="${event['image']}" class="card-img-top w-100" style="object-fit: cover; height: 100%;" alt="Event Image">
                            ${isToday ? `<span class="today-badge"><i class="fas fa-clock me-1"></i>${dict.todayBadge}</span>` : ''}
                            ${isExpired ? `<span class="expired-badge"><i class="fas fa-check-circle me-1"></i>${dict.completedBadge}</span>` : ''}
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="sdg-tag">${getTranslatedSdg(event['sdg'], currentLang)}</span>
                                ${event['the_cat'] ? `<span class="criteria-tag"><i class="fas fa-university me-1"></i>${getTranslatedThe(event['the_cat'], currentLang)}</span>` : ''}
                                ${event['qs_cat'] ? `<span class="criteria-tag"><i class="fas fa-star me-1"></i>${getTranslatedQs(event['qs_cat'], currentLang)}</span>` : ''}
                            </div>

                            <h5 class="card-title fw-bold mb-2 text-adaptive">
                                ${event['title']}
                            </h5>
                            <p class="card-text text-muted-adaptive small mb-3">
                                ${event['description']}
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between text-muted-adaptive small border-top pt-3 mb-3" style="border-color: var(--border-color) !important;">
                                    <span><i class="far fa-building me-1"></i> ${event['organizer']}</span>
                                    <span><i class="far fa-calendar me-1"></i> ${new Date(event['date']).toLocaleDateString(locale, {day: '2-digit', month: 'short', year: 'numeric'})} ${dict.dateAt} ${event['time']}</span>
                                </div>
                                
                                <button onclick="generatePDF(${event['id']})" class="btn btn-outline-danger btn-sm w-100" ${!isExpired ? 'style="display:none;"' : ''}>
                                    <i class="fas fa-file-pdf me-2"></i>${dict.downloadReport}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                `;
                
                container.insertAdjacentHTML('beforeend', eventHTML);
            }
            
            currentLoadIndex = endIndex;
            isLoadingMore = false;
            
            // Re-apply current filters to new events
            executeFilter();
        }

        // --- 4. INTERSECTION OBSERVER FOR LAZY LOADING ---
        function setupInfiniteScroll() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && currentLoadIndex < allEventsData.length) {
                        loadMoreEvents();
                    }
                });
            }, {
                rootMargin: '200px' // Load when 200px before reaching bottom
            });
            
            // Create sentinel element
            const sentinel = document.createElement('div');
            sentinel.id = 'scroll-sentinel';
            document.getElementById('events-container').parentElement.appendChild(sentinel);
            observer.observe(sentinel);
        }

        // --- 5. FILTERING ---
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');

        function executeFilter() {
            const searchQuery = searchInput.value.toLowerCase();
            const selectedSDGs = Array.from(document.querySelectorAll('input[name="sdg"]:checked')).map(cb => cb.value);
            const selectedTHE = Array.from(document.querySelectorAll('input[name="the"]:checked')).map(cb => cb.value);
            const selectedQS = Array.from(document.querySelectorAll('input[name="qs"]:checked')).map(cb => cb.value);
            const hideExpired = document.getElementById('hideExpiredCheck').checked;

            const events = document.querySelectorAll('.event-item');
            let visibleCount = 0;

            events.forEach(event => {
                const eventSDG = event.getAttribute('data-sdg');
                const eventTHE = event.getAttribute('data-the');
                const eventQS = event.getAttribute('data-qs');
                const eventTitle = event.querySelector('.card-title').innerText.toLowerCase();
                const eventDesc = event.querySelector('.card-text').innerText.toLowerCase();
                const isExpired = event.getAttribute('data-expired') === '1';

                const matchSDG = selectedSDGs.length === 0 || selectedSDGs.includes(eventSDG);
                const matchTHE = selectedTHE.length === 0 || selectedTHE.includes(eventTHE);
                const matchQS = selectedQS.length === 0 || selectedQS.includes(eventQS);
                const matchSearch = (searchQuery === "") || (eventTitle.includes(searchQuery) || eventDesc.includes(searchQuery));
                const matchExpired = !hideExpired || !isExpired;

                if (matchSDG && matchTHE && matchQS && matchSearch && matchExpired) {
                    event.style.display = 'block'; visibleCount++;
                } else {
                    event.style.display = 'none';
                }
            });
            document.getElementById('no-results').style.display = visibleCount === 0 ? 'block' : 'none';
            updateEventCounter();
        }
        
        searchBtn.addEventListener('click', executeFilter);
        searchInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') executeFilter(); });
        document.getElementById('hideExpiredCheck').addEventListener('change', executeFilter);
        
        // Add change listeners to all filter checkboxes
        document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', executeFilter);
        });
        
        // Page load
        window.addEventListener('DOMContentLoaded', async function() {
            allEventsData = await fetchEventsForLang(currentLang);
            totalEventCount = allEventsData.length;

            // Remove static events from HTML
            const container = document.getElementById('events-container');
            container.innerHTML = '';
            
            // Load initial batch
            loadMoreEvents();
            
            // Setup infinite scroll
            setupInfiniteScroll();

            renderCalendarSidebar(allEventsData, currentLang);
            
            // Apply filters after a small delay to ensure DOM is ready
            setTimeout(() => {
                applyTranslations(currentLang);
                executeFilter();
                updateEventCounter();
            }, 100);
        });

        function rerenderEventsForLanguage() {
            const container = document.getElementById('events-container');
            container.innerHTML = '';
            currentLoadIndex = 0;
            loadMoreEvents();
            executeFilter();
            updateEventCounter();
        }
        
        function updateEventCounter() {
            const searchQuery = document.getElementById('search-input').value.toLowerCase();
            const selectedSDGs = Array.from(document.querySelectorAll('input[name="sdg"]:checked')).length;
            const selectedTHE = Array.from(document.querySelectorAll('input[name="the"]:checked')).length;
            const selectedQS = Array.from(document.querySelectorAll('input[name="qs"]:checked')).length;
            const hideExpired = document.getElementById('hideExpiredCheck').checked;
            
            // If no filters are applied, show all events count
            const hasFilters = selectedSDGs > 0 || selectedTHE > 0 || selectedQS > 0 || hideExpired || searchQuery !== '';
            
            let visibleEvents;
            if (!hasFilters) {
                // No filters - show total event count
                visibleEvents = totalEventCount;
            } else {
                // Filters applied - count visible ones in DOM
                const allEvents = document.querySelectorAll('.event-item');
                visibleEvents = Array.from(allEvents).filter(el => el.style.display !== 'none').length;
            }
            
            const counterEl = document.getElementById('event-counter');
            
            // Remove animation class
            counterEl.classList.remove('counter-animate');
            
            // Trigger reflow to restart animation
            void counterEl.offsetWidth;
            
            // Add animation class and update value
            counterEl.classList.add('counter-animate');
            
            // Count up animation from 0 to visibleEvents
            let currentCount = 0;
            const increment = Math.ceil(visibleEvents / 30); // 30 steps for smooth animation
            const interval = setInterval(() => {
                currentCount += increment;
                if (currentCount >= visibleEvents) {
                    currentCount = visibleEvents;
                    clearInterval(interval);
                }
                counterEl.innerText = currentCount + '/' + totalEventCount;
            }, 30);
        }

        // --- 6. REAL PDF GENERATION (jsPDF) ---
        function generatePDF(eventId) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Find the event data
            const event = allEventsData.find(e => e.id == eventId);
            if (!event) return;

            // --- HEADER DESIGN ---
            // Corporate Blue Box
            doc.setFillColor(0, 33, 71); // EMU Blue
            doc.rect(0, 0, 210, 40, 'F');
            
            // White Text for Header
            doc.setTextColor(255, 255, 255);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(22);
            doc.text("EASTERN MEDITERRANEAN UNIVERSITY", 105, 20, { align: "center" });
            
            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            doc.text("Activity & Event Reporting System", 105, 30, { align: "center" });

            // Orange Line
            doc.setDrawColor(245, 130, 32); // EMU Orange
            doc.setLineWidth(1.5);
            doc.line(0, 41, 210, 41);

            // --- REPORT METADATA (Top Right) ---
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            // Tarih: Etkinlik Tarihi (Event Date)
            doc.text(`Report ID: EMU-${event.id}-${new Date().getFullYear()}`, 150, 55);
            doc.text(`Activity Date: ${event.date}`, 150, 60);

            // --- TITLE SECTION ---
            doc.setFontSize(18);
            doc.setFont("helvetica", "bold");
            doc.setTextColor(0, 33, 71);
            doc.text("OFFICIAL ACTIVITY REPORT", 14, 60);
            
            doc.setDrawColor(200, 200, 200);
            doc.line(14, 65, 196, 65);

            // --- DETAILED CONTENT TABLE ---
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
                headStyles: { fillColor: [0, 90, 156] }, // Light Blue
                columnStyles: { 0: { fontStyle: 'bold', cellWidth: 50 } }
            });

            // --- DESCRIPTION ---
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

            // --- FOOTER ---
            const pageHeight = doc.internal.pageSize.height;
            doc.setDrawColor(245, 130, 32);
            doc.line(14, pageHeight - 30, 196, pageHeight - 30);
            
            doc.setFontSize(9);
            doc.setTextColor(100, 100, 100);
            doc.text("This document is automatically generated by the EMU Activity Reporting System.", 14, pageHeight - 20);
            doc.text("Accreditation & Quality Assurance Office", 14, pageHeight - 15);
            
            doc.save(`EMU_Report_${event.date}_${event.id}.pdf`);
        }

        // --- 7. LOGIN MODAL ---
        // login.php sends visitors back with ?login=failed or ?login=required; reopen the modal for them.
        const loginState = new URLSearchParams(window.location.search).get('login');
        if (loginState) {
            if (loginState === 'failed') document.getElementById('login-error').style.display = 'block';
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        }
    </script>
</body>
</html>