<?php
/**
 * EMU Activity & Event Reporting Portal
 * Prototype v10
 * * Features: Login Modal, Role-Based Redirects (Admin/User), PDF Generation.
 */

// --- DATA SOURCE CONFIGURATION ---
$json_file = 'events.json';
$events = [];

// Load JSON
if (file_exists($json_file)) {
    $events = json_decode(file_get_contents($json_file), true) ?? [];
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
                    <li class="nav-item"><a class="nav-link" href="calendar.php"><i class="far fa-calendar-alt me-1"></i> Calendar</a></li>
                    <li class="nav-item"><a class="nav-link" href="#all-events-section"><i class="fas fa-list me-1"></i> Events</a></li>
                    <li class="nav-item">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="btn btn-login btn-sm px-4 rounded-pill">
                            <i class="fas fa-lock me-2"></i>Staff Login
                        </a>
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

    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-5 fw-bold mb-3">Activity & Event Reporting</h1>
            <p class="lead mb-0" style="opacity: 0.9;">
                Track university achievements, SDG contributions, and accreditation standards.
            </p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            
            <div class="col-lg-3 mb-4">
                <div id="calendar" class="calendar-widget p-3 mb-4 rounded-1">
                    <h5 class="mb-3 text-adaptive border-bottom pb-2">Upcoming Calendar</h5>
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

                <div class="filter-box-container p-3 shadow-sm rounded-1">
                    <h5 class="mb-3 text-adaptive"><i class="fas fa-filter me-2"></i>Filters</h5>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted-adaptive fw-bold">Search</label>
                        <div class="input-group">
                            <input type="text" id="search-input" class="form-control form-control-sm" placeholder="Title, keyword...">
                            <button class="btn btn-outline-secondary" type="button" id="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted-adaptive fw-bold">UN Sustainable Goals</label>
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
                        <label class="form-label small text-muted-adaptive fw-bold">THE Impact Ranking</label>
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
                        <label class="form-label small text-muted-adaptive fw-bold">QS Stars Evaluation</label>
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

                    <button id="apply-filters-btn" class="btn btn-primary w-100 btn-sm" style="background-color: var(--emu-light-blue); border:none;">
                        Apply Filters
                    </button>
                    <button onclick="location.reload()" class="btn btn-outline-secondary w-100 btn-sm mt-2 text-adaptive">
                        Reset
                    </button>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4" id="all-events-section">
                    <h3 class="fw-bold m-0 text-adaptive">All Events</h3>
                </div>

                <div class="row" id="events-container">
                    <?php if(empty($events)): ?>
                        <div class="col-12"><div class="alert alert-info">No events found or events.json is missing.</div></div>
                    <?php else: ?>
                        <?php foreach($events as $event): ?>
                        <div class="col-md-6 mb-4 event-item" 
                             data-sdg="<?php echo $event['sdg']; ?>" 
                             data-the="<?php echo $event['the_cat'] ?? ''; ?>" 
                             data-qs="<?php echo $event['qs_cat'] ?? ''; ?>">
                             
                            <div class="card event-card h-100">
                                <div style="height: 160px; overflow: hidden; background: #eee;">
                                    <img src="<?php echo $event['image']; ?>" class="w-100" style="object-fit: cover; height: 100%;" alt="Event Image">
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    <div class="mb-2">
                                        <span class="sdg-tag"><?php echo $event['sdg']; ?></span>
                                        <?php if(isset($event['the_cat'])): ?>
                                            <span class="criteria-tag"><i class="fas fa-university me-1"></i><?php echo $event['the_cat']; ?></span>
                                        <?php endif; ?>
                                        <?php if(isset($event['qs_cat'])): ?>
                                            <span class="criteria-tag"><i class="fas fa-star me-1"></i><?php echo $event['qs_cat']; ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <h5 class="card-title fw-bold mb-2 text-adaptive">
                                        <?php echo $event['title']; ?>
                                    </h5>
                                    <p class="card-text text-muted-adaptive small mb-3">
                                        <?php echo $event['description']; ?>
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between text-muted-adaptive small border-top pt-3 mb-3" style="border-color: var(--border-color) !important;">
                                            <span><i class="far fa-building me-1"></i> <?php echo $event['organizer']; ?></span>
                                            <span><i class="far fa-calendar me-1"></i> <?php echo date('d M Y', strtotime($event['date'])); ?></span>
                                        </div>
                                        
                                        <button onclick="generatePDF(<?php echo $event['id']; ?>)" class="btn btn-outline-danger btn-sm w-100">
                                            <i class="fas fa-file-pdf me-2"></i>Download Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div id="no-results" class="alert alert-warning text-center" style="display:none;">
                    No events found matching your search or filters.
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-1 text-white fw-bold">Eastern Mediterranean University</p>
            <p class="small text-white mb-0">Software Engineering Project Prototype &copy; 2025</p>
        </div>
    </footer>

    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--emu-dark-blue); color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-shield me-2"></i>Staff Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Please enter your institutional credentials.</p>
                    <div class="mb-3">
                        <label for="username" class="form-label fw-bold small">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-bold small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" placeholder="******">
                        </div>
                    </div>
                    
                    <div id="login-error" class="alert alert-danger py-2 small" style="display:none;">
                        <i class="fas fa-exclamation-circle me-1"></i> Invalid username or password.
                    </div>

                    <button type="button" onclick="performLogin()" class="btn btn-primary w-100" style="background-color: var(--emu-orange); border:none;">
                        Login
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // --- 1. DATA TRANSFER FROM PHP TO JS ---
        const allEventsData = <?php echo json_encode($events); ?>;

        // --- 2. DARK MODE ---
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

        // --- 3. FILTERING ---
        const applyBtn = document.getElementById('apply-filters-btn');
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');

        function executeFilter() {
            const searchQuery = searchInput.value.toLowerCase();
            const selectedSDGs = Array.from(document.querySelectorAll('input[name="sdg"]:checked')).map(cb => cb.value);
            const selectedTHE = Array.from(document.querySelectorAll('input[name="the"]:checked')).map(cb => cb.value);
            const selectedQS = Array.from(document.querySelectorAll('input[name="qs"]:checked')).map(cb => cb.value);

            const events = document.querySelectorAll('.event-item');
            let visibleCount = 0;

            events.forEach(event => {
                const eventSDG = event.getAttribute('data-sdg');
                const eventTHE = event.getAttribute('data-the');
                const eventQS = event.getAttribute('data-qs');
                const eventTitle = event.querySelector('.card-title').innerText.toLowerCase();
                const eventDesc = event.querySelector('.card-text').innerText.toLowerCase();

                const matchSDG = selectedSDGs.length === 0 || selectedSDGs.includes(eventSDG);
                const matchTHE = selectedTHE.length === 0 || selectedTHE.includes(eventTHE);
                const matchQS = selectedQS.length === 0 || selectedQS.includes(eventQS);
                const matchSearch = (searchQuery === "") || (eventTitle.includes(searchQuery) || eventDesc.includes(searchQuery));

                if (matchSDG && matchTHE && matchQS && matchSearch) {
                    event.style.display = 'block'; visibleCount++;
                } else {
                    event.style.display = 'none';
                }
            });
            document.getElementById('no-results').style.display = visibleCount === 0 ? 'block' : 'none';
        }
        applyBtn.addEventListener('click', executeFilter);
        searchBtn.addEventListener('click', executeFilter);
        searchInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') executeFilter(); });

        // --- 4. REAL PDF GENERATION (jsPDF) ---
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

        // --- 5. LOGIN LOGIC (NEW) ---
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
                // Shake animation effect for invalid login
                const modalContent = document.querySelector('.modal-content');
                modalContent.classList.add('border-danger');
                setTimeout(() => { modalContent.classList.remove('border-danger'); }, 500);
            }
        }
        
        // Allow pressing "Enter" inside inputs to trigger login
        document.getElementById('password').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') performLogin();
        });
    </script>
</body>
</html>