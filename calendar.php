<?php
/**
 * EMU Activity & Event Reporting Portal
 * Calendar View Page
 * * Features: Interactive JS Calendar, Month/Year Navigation, Event Highlighting.
 * * @version 2.0.0
 */

// --- DATA LOADING ---
$json_file = 'events.json';
$events = [];

if (file_exists($json_file)) {
    $events = json_decode(file_get_contents($json_file), true) ?? [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMU | Event Calendar</title>
    <link rel="icon" type="image/x-icon" href="images/logo.ico">    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
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
            --cal-hover: #f0f0f0;
        }

        [data-theme="dark"] {
            --emu-dark-blue: #00152e;
            --emu-light-blue: #3a86cc;
            --bg-body: #121212;
            --bg-card: #1e1e1e;
            --text-main: #e0e0e0;
            --text-muted: #aaaaaa;
            --border-color: #333333;
            --cal-hover: #2c2c2c;
        }

        [data-theme="dark"] .modal-content { background-color: #1e1e1e; color: #e0e0e0; }
        [data-theme="dark"] .modal-body { background-color: #1e1e1e; }
        [data-theme="dark"] .input-group-text { background-color: #333; border-color: #444; color: #aaa; }
        [data-theme="dark"] .form-control { background-color: #333; border-color: #444; color: #fff; }
        [data-theme="dark"] .text-muted { color: #aaa !important; }

        body {
            background-color: var(--bg-body);
            font-family: 'Open Sans', sans-serif;
            color: var(--text-main);
            transition: background-color 0.3s, color 0.3s;
        }

        h1, h2, h3, h4, h5 { font-family: 'Merriweather', serif; }

        /* Navbar & Header (Consistent with Index) */
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

        .btn, .btn:focus { transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(0,0,0,0.12); }
        .calendar-container, .event-list-item { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .calendar-container:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.12); }

        .hero-section {
            background: linear-gradient(rgba(0, 33, 71, 0.9), rgba(0, 33, 71, 0.8)), url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Eastern_Mediterranean_University_Rectorate_Building.jpg/1200px-Eastern_Mediterranean_University_Rectorate_Building.jpg');
            background-position: center;
            background-size: cover;
            color: white;
            padding: 60px 0;
            border-bottom: 5px solid var(--emu-orange);
        }

        .hero-fade { opacity: 0; transform: translateY(8px); transition: all 0.6s ease; }
        .hero-fade.show { opacity: 1; transform: translateY(0); }
        .navbar-scrolled { box-shadow: 0 4px 18px rgba(0,0,0,0.18); background-color: #001a38; transition: box-shadow 0.3s ease, background-color 0.3s ease; }

        /* --- INTERACTIVE CALENDAR STYLES --- */
        .calendar-container {
            background: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .calendar-header-bar {
            background-color: var(--emu-dark-blue);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .calendar-header-bar h4 { margin: 0; font-size: 1.2rem; }
        .btn-nav {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 4px;
            padding: 5px 10px;
            transition: all 0.2s;
        }
        .btn-nav:hover { background: rgba(255,255,255,0.2); }

        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: 10px 0;
        }
        .weekdays div {
            text-align: center;
            font-weight: bold;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            padding: 10px;
            background-color: var(--bg-card);
        }
        .days div {
            min-height: 80px; /* Height of each day cell */
            padding: 5px;
            border: 1px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            color: var(--text-main);
            transition: background 0.2s;
        }
        .days div:hover:not(.empty) {
            background-color: var(--cal-hover);
            border-color: var(--border-color);
        }
        .days div.today {
            background-color: rgba(245, 130, 32, 0.1); /* Light Orange */
            border: 1px solid var(--emu-orange);
            font-weight: bold;
        }
        .days div.has-event::after {
            content: '';
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: var(--emu-light-blue);
            border-radius: 50%;
        }
        .prev-date, .next-date {
            opacity: 0.3;
        }

        /* Event List Sidebar */
        .event-list-item {
            border-left: 4px solid var(--emu-orange);
            background: var(--bg-card);
            margin-bottom: 10px;
            padding: 15px;
            transition: transform 0.2s;
            border-top: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }
        .event-list-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .event-list-item small { color: var(--text-muted) !important; }

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
            <a class="navbar-brand" href="index.php">
                <img src="https://www.emu.edu.tr/static/images/logos/emu-logo-horizontalwhite-tr.svg" alt="EMU Logo">
            </a>
            
            <button class="navbar-toggler navbar-dark border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" id="nav-home" href="index.php"> <i class="fas fa-home me-1"></i> Home</a></li>
                    <li class="nav-item">
                        <a id="nav-login" href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="btn btn-login btn-sm px-4 rounded-pill">
                            <i class="fas fa-lock me-2"></i>Staff Login
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
            <h1 class="display-5 fw-bold mb-3" id="hero-title">Event Calendar</h1>
            <p class="lead mb-0" style="opacity: 0.9;" id="hero-subtitle">
                View upcoming university activities, workshops, and deadlines.
            </p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            
            <div class="col-lg-8 mb-4">
                <div class="calendar-container">
                    <div class="calendar-header-bar">
                        <button class="btn-nav" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                        <div class="text-center">
                            <h4 id="monthYearDisplay">Loading...</h4>
                            <small id="goto-today" onclick="jumpToToday()" style="cursor:pointer; text-decoration:underline; opacity:0.8;">Go to Today</small>
                        </div>
                        <button class="btn-nav" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>

                    <div class="weekdays" id="weekdays-row"></div>

                    <div class="days" id="daysGrid">
                        </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0" style="color: var(--text-main);" id="events-heading">Events</h5>
                    <span class="badge bg-secondary" id="selectedDateLabel">All Upcoming</span>
                </div>
                
                <div id="eventListContainer"></div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-1 text-white fw-bold">Eastern Mediterranean University</p>
            <p class="small text-white mb-0">Software Engineering Course Prototype &copy; 2025 &middot; Not an official EMU service &middot; Sample data only, no real authentication</p>
        </div>
    </footer>

    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--emu-dark-blue); color: white;">
                    <h5 class="modal-title" id="login-modal-title"><i class="fas fa-user-shield me-2"></i>Staff Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3" id="login-desc">Please enter your institutional credentials.</p>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold small" id="username-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold small" id="password-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" placeholder="******">
                        </div>
                    </div>
                    
                    <div id="login-error" class="alert alert-danger py-2 small" style="display:none;">
                        <i class="fas fa-exclamation-circle me-1"></i> <span id="login-error-text">Invalid username or password.</span>
                    </div>

                    <button type="button" onclick="performLogin()" class="btn btn-primary w-100" style="background-color: var(--emu-orange); border:none;" id="login-btn">
                        Login
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 1. Data + Localization Setup
        let eventsData = <?php echo json_encode($events); ?>;
        let dataCache = { en: eventsData, tr: null };
        let selectedDate = null;
        const today = new Date();
        let currYear = today.getFullYear();
        let currMonth = today.getMonth();
        let currentLang = localStorage.getItem('lang') || 'en';

        const monthYearDisplay = document.getElementById('monthYearDisplay');
        const daysGrid = document.getElementById('daysGrid');
        const prevBtn = document.getElementById('prevMonth');
        const nextBtn = document.getElementById('nextMonth');
        const weekdaysRow = document.getElementById('weekdays-row');
        const langToggleBtn = document.getElementById('lang-toggle');

        const translations = {
            en: {
                navHome: '<i class="fas fa-home me-1"></i> Home',
                navLogin: '<i class="fas fa-lock me-2"></i>Staff Login',
                heroTitle: 'Event Calendar',
                heroSubtitle: 'View upcoming university activities, workshops, and deadlines.',
                gotoToday: 'Go to Today',
                eventsHeading: 'Events',
                allUpcoming: 'All Upcoming',
                noEventsDb: 'No events found in database.',
                noEventsDay: 'No events on this day.',
                todayBadge: 'TODAY',
                endedBadge: 'ENDED',
                loginModalTitle: '<i class="fas fa-user-shield me-2"></i>Staff Login',
                loginDesc: 'Please enter your institutional credentials.',
                usernameLabel: 'Username',
                passwordLabel: 'Password',
                loginError: 'Invalid username or password.',
                loginBtn: 'Login'
            },
            tr: {
                navHome: '<i class="fas fa-home me-1"></i> Ana Sayfa',
                navLogin: '<i class="fas fa-lock me-2"></i>Personel Girişi',
                heroTitle: 'Etkinlik Takvimi',
                heroSubtitle: 'Yaklaşan üniversite etkinliklerini, atölyeleri ve son tarihleri görüntüleyin.',
                gotoToday: 'Bugüne Git',
                eventsHeading: 'Etkinlikler',
                allUpcoming: 'Tüm Yaklaşanlar',
                noEventsDb: 'Veritabanında etkinlik bulunamadı.',
                noEventsDay: 'Bu günde etkinlik yok.',
                todayBadge: 'BUGÜN',
                endedBadge: 'BİTTİ',
                loginModalTitle: '<i class="fas fa-user-shield me-2"></i>Personel Girişi',
                loginDesc: 'Lütfen kurum hesabınızla giriş yapın.',
                usernameLabel: 'Kullanıcı Adı',
                passwordLabel: 'Şifre',
                loginError: 'Geçersiz kullanıcı adı veya şifre.',
                loginBtn: 'Giriş Yap'
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

        const monthNames = {
            en: ['January','February','March','April','May','June','July','August','September','October','November','December'],
            tr: ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık']
        };
        const weekdayNames = {
            en: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
            tr: ['Pzt','Sal','Çar','Per','Cum','Cts','Paz']
        };

        function applyTranslations(lang) {
            const dict = translations[lang] || translations.en;
            const targets = {
                'nav-home': 'navHome',
                'nav-login': 'navLogin',
                'hero-title': 'heroTitle',
                'hero-subtitle': 'heroSubtitle',
                'goto-today': 'gotoToday',
                'events-heading': 'eventsHeading',
                'login-modal-title': 'loginModalTitle',
                'login-desc': 'loginDesc',
                'username-label': 'usernameLabel',
                'password-label': 'passwordLabel',
                'login-error-text': 'loginError',
                'login-btn': 'loginBtn'
            };
            Object.entries(targets).forEach(([id, key]) => {
                const el = document.getElementById(id);
                if (el && dict[key]) el.innerHTML = dict[key];
            });
            const selLabel = document.getElementById('selectedDateLabel');
            if (selLabel && !selectedDate) selLabel.innerText = dict.allUpcoming;
            langToggleBtn.textContent = lang === 'en' ? 'TR' : 'EN';
            renderWeekdays(lang);
            renderCalendar();
        }

        function sortEvents(events) {
            const now = Date.now();
            const todayStart = new Date(new Date().toDateString()).getTime();
            const todayEnd = todayStart + 86400000;
            return [...events].sort((a, b) => {
                const aTime = new Date(`${a.date} ${a.time}`).getTime();
                const bTime = new Date(`${b.date} ${b.time}`).getTime();
                const aDay = new Date(a.date).setHours(0,0,0,0);
                const bDay = new Date(b.date).setHours(0,0,0,0);

                const isTodayA = aDay >= todayStart && aDay < todayEnd && aTime >= now;
                const isTodayB = bDay >= todayStart && bDay < todayEnd && bTime >= now;
                const isExpiredA = aTime < now;
                const isExpiredB = bTime < now;

                if (isTodayA && !isTodayB) return -1;
                if (!isTodayA && isTodayB) return 1;
                if (isTodayA && isTodayB) return aTime - bTime;
                if (!isExpiredA && !isExpiredB) return aDay - bDay;
                if (isExpiredA && isExpiredB) return bDay - aDay;
                return isExpiredA ? 1 : -1;
            });
        }

        eventsData = sortEvents(eventsData);
        dataCache.en = eventsData;

        function fetchEventsForLang(lang) {
            if (dataCache[lang]) return Promise.resolve(dataCache[lang]);
            const file = lang === 'tr' ? 'eventstr.json' : 'events.json';
            return fetch(file)
                .then(r => r.ok ? r.json() : [])
                .then(data => {
                    const list = Array.isArray(data) ? sortEvents(data) : [];
                    dataCache[lang] = list;
                    return list;
                })
                .catch(() => dataCache[lang] || []);
        }

        function renderWeekdays(lang) {
            const names = weekdayNames[lang] || weekdayNames.en;
            weekdaysRow.innerHTML = names.map(d => `<div>${d}</div>`).join('');
        }

        function isEventExpired(ev) {
            const eventTime = new Date(`${ev.date} ${ev.time}`).getTime();
            return eventTime < Date.now();
        }

        function isEventTodayUpcoming(ev) {
            const now = Date.now();
            const eventTime = new Date(`${ev.date} ${ev.time}`).getTime();
            const eventDay = new Date(ev.date).setHours(0, 0, 0, 0);
            const todayDay = new Date().setHours(0, 0, 0, 0);
            return eventDay === todayDay && eventTime >= now;
        }

        function getUpcomingEvents() {
            return (eventsData || []).filter(ev => !isEventExpired(ev));
        }

        function renderCalendar() {
            let firstDayOfMonth = new Date(currYear, currMonth, 1).getDay();
            firstDayOfMonth = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1; // Mon-first
            const lastDateOfMonth = new Date(currYear, currMonth + 1, 0).getDate();
            const lastDateOfLastMonth = new Date(currYear, currMonth, 0).getDate();
            const locale = currentLang === 'tr' ? 'tr-TR' : 'en-US';

            let html = '';
            for (let i = firstDayOfMonth; i > 0; i--) {
                html += `<div class="prev-date">${lastDateOfLastMonth - i + 1}</div>`;
            }
            for (let i = 1; i <= lastDateOfMonth; i++) {
                const isToday = i === today.getDate() && currMonth === today.getMonth() && currYear === today.getFullYear() ? 'today' : '';
                const monthStr = (currMonth + 1).toString().padStart(2, '0');
                const dayStr = i.toString().padStart(2, '0');
                const dateString = `${currYear}-${monthStr}-${dayStr}`;
                const hasEvent = eventsData.some(e => e.date === dateString) ? 'has-event' : '';
                html += `<div class="${isToday} ${hasEvent}" onclick="filterEventsByDate('${dateString}')">${i}</div>`;
            }
            daysGrid.innerHTML = html;
            const monthName = (monthNames[currentLang] || monthNames.en)[currMonth];
            monthYearDisplay.innerText = `${monthName} ${currYear}`;
        }

        prevBtn.addEventListener('click', () => {
            currMonth--;
            if (currMonth < 0) { currMonth = 11; currYear--; }
            renderCalendar();
        });
        nextBtn.addEventListener('click', () => {
            currMonth++;
            if (currMonth > 11) { currMonth = 0; currYear++; }
            renderCalendar();
        });

        function jumpToToday() {
            currYear = today.getFullYear();
            currMonth = today.getMonth();
            selectedDate = null;
            const dict = translations[currentLang] || translations.en;
            document.getElementById('selectedDateLabel').innerText = dict.allUpcoming;
            renderCalendar();
            renderEventList(getUpcomingEvents());
        }

        function filterEventsByDate(dateStr) {
            selectedDate = dateStr;
            const filteredEvents = eventsData.filter(e => e.date === dateStr);
            document.getElementById('selectedDateLabel').innerText = dateStr;
            renderEventList(filteredEvents);
        }

        function renderEventList(data) {
            const container = document.getElementById('eventListContainer');
            const dict = translations[currentLang] || translations.en;
            if (!data || data.length === 0) {
                const fallbackMsg = (eventsData || []).length === 0 && !selectedDate ? dict.noEventsDb : dict.noEventsDay;
                container.innerHTML = `<div class="alert alert-light border">${fallbackMsg}</div>`;
                return;
            }
            const locale = currentLang === 'tr' ? 'tr-TR' : 'en-US';
            const markup = data.map(event => {
                const dateStr = new Date(event.date).toLocaleDateString(locale, { day: '2-digit', month: 'short', year: 'numeric' });
                const sdg = currentLang === 'tr' ? (sdgTranslations.tr[event.sdg] || event.sdg) : event.sdg;
                const badge = isEventTodayUpcoming(event)
                    ? `<span class="badge bg-warning text-dark ms-2" style="font-size:0.65rem;">${dict.todayBadge}</span>`
                    : (isEventExpired(event) ? `<span class="badge bg-secondary ms-2" style="font-size:0.65rem;">${dict.endedBadge}</span>` : '');
                return `
                    <div class="event-list-item rounded">
                        <h6 class="fw-bold mb-1" style="color: var(--text-main);">${event.title}</h6>
                        <small class="d-block mb-1" style="color: var(--text-muted);">
                            <i class="far fa-clock me-1"></i> ${dateStr} | ${event.time}
                        </small>
                        <span class="badge bg-success" style="font-size: 0.7rem;">${sdg}</span>${badge}
                        <div class="mt-2">
                            <small class="text-muted" style="font-size:0.75rem;">${event.organizer}</small>
                        </div>
                    </div>`;
            }).join('');
            container.innerHTML = markup;
        }

        function setLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('lang', lang);
            fetchEventsForLang(lang).then(data => {
                eventsData = data;
                selectedDate = null;
                applyTranslations(lang);
                renderEventList(getUpcomingEvents());
            }).catch(() => {
                applyTranslations(lang);
                renderEventList(getUpcomingEvents());
            });
        }

        langToggleBtn.addEventListener('click', () => {
            const nextLang = currentLang === 'en' ? 'tr' : 'en';
            setLanguage(nextLang);
        });

        // --- Login Logic ---
        function performLogin() {
            const u = document.getElementById('username').value.trim();
            const p = document.getElementById('password').value.trim();
            const errorBox = document.getElementById('login-error');

            if (u === 'admin' && p === 'admin') {
                window.location.href = 'admin_dashboard.php';
            } else if (u === 'user' && p === 'user') {
                window.location.href = 'user_dashboard.php';
            } else {
                errorBox.style.display = 'block';
                const modalContent = document.querySelector('.modal-content');
                modalContent.classList.add('border-danger');
                setTimeout(() => { modalContent.classList.remove('border-danger'); }, 500);
            }
        }
        document.getElementById('password').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') performLogin();
        });

        // --- Dark Mode Logic ---
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

        // Initial load
        (async function init() {
            eventsData = await fetchEventsForLang(currentLang);
            dataCache[currentLang] = eventsData;
            renderWeekdays(currentLang);
            applyTranslations(currentLang);
            renderCalendar();
            renderEventList(getUpcomingEvents());
        })();
    </script>
</body>
</html>