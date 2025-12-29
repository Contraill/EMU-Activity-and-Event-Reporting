<?php
/**
 * EMU Activity & Event Reporting Portal
 * Frontend Prototype
 * Features: Search Functionality, JS Filters, PDF Mockup, Dark Mode
 */

// --- MOCK DATA ---
$events = [
    [
        'id' => 1,
        'title' => 'Marine Life & Sustainability Panel',
        'date' => '2025-12-15',
        'time' => '14:30',
        'description' => 'A comprehensive seminar regarding the protection of sea turtles and marine ecosystems in Cyprus.',
        'sdg' => 'SDG 14: Life Below Water',
        'the_cat' => 'Research',
        'qs_cat' => 'Research Quality',
        'organizer' => 'Biological Sciences Dept.',
        'image' => 'https://via.placeholder.com/600x400?text=Marine+Protection' 
    ],
    [
        'id' => 2,
        'title' => 'Renewable Energy Systems Workshop',
        'date' => '2025-12-20',
        'time' => '09:00',
        'description' => 'Hands-on workshop focusing on solar panel efficiency and green energy transition strategies.',
        'sdg' => 'SDG 7: Affordable and Clean Energy',
        'the_cat' => 'Teaching',
        'qs_cat' => 'Teaching & Learning',
        'organizer' => 'Faculty of Engineering',
        'image' => 'https://via.placeholder.com/600x400?text=Solar+Energy'
    ],
    [
        'id' => 3,
        'title' => 'Campus Mental Health Awareness',
        'date' => '2025-12-22',
        'time' => '11:00',
        'description' => 'Open session for students: Stress management techniques and psychological support resources.',
        'sdg' => 'SDG 3: Good Health and Well-being',
        'the_cat' => 'Teaching',
        'qs_cat' => 'Inclusiveness',
        'organizer' => 'PDRAM',
        'image' => 'https://via.placeholder.com/600x400?text=Mental+Health'
    ],
    [
        'id' => 4,
        'title' => 'International Research Collaboration',
        'date' => '2026-01-05',
        'time' => '10:00',
        'description' => 'Meeting with foreign partners to discuss joint research projects and funding.',
        'sdg' => 'SDG 17: Partnerships for the Goals',
        'the_cat' => 'International Outlook',
        'qs_cat' => 'Internationalization',
        'organizer' => 'Rectorate Office',
        'image' => 'https://via.placeholder.com/600x400?text=Global+Partners'
    ]
];

// Helper arrays
$sdgs = [
    "SDG 1: No Poverty", "SDG 2: Zero Hunger", "SDG 3: Good Health and Well-being",
    "SDG 4: Quality Education", "SDG 5: Gender Equality", "SDG 6: Clean Water and Sanitation",
    "SDG 7: Affordable and Clean Energy", "SDG 8: Decent Work and Economic Growth",
    "SDG 9: Industry, Innovation and Infrastructure", "SDG 10: Reduced Inequalities",
    "SDG 11: Sustainable Cities and Communities", "SDG 12: Responsible Consumption and Production",
    "SDG 13: Climate Action", "SDG 14: Life Below Water", "SDG 15: Life on Land",
    "SDG 16: Peace, Justice and Strong Institutions", "SDG 17: Partnerships for the Goals"
];

$the_criteria = [
    "Teaching", "Research", "Citations Impact", "International Outlook", "Industry Income / Collaboration"
];

$qs_criteria = [
    "Teaching & Learning", "Employability", "Research Quality", "Internationalization", 
    "Inclusiveness", "Social Responsibility"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMU | Activity & Event Reporting Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Light Mode */
            --emu-dark-blue: #002147; 
            --emu-light-blue: #005A9C;
            --emu-orange: #F58220;
            --bg-body: #f4f4f4;
            --bg-card: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
        }

        /* Dark Mode */
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

        /* Navbar */
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

        /* Hero */
        .hero-section {
            background: linear-gradient(rgba(0, 33, 71, 0.9), rgba(0, 33, 71, 0.8)), url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Eastern_Mediterranean_University_Rectorate_Building.jpg/1200px-Eastern_Mediterranean_University_Rectorate_Building.jpg');
            background-position: center;
            background-size: cover;
            color: white;
            padding: 60px 0;
            border-bottom: 5px solid var(--emu-orange);
        }

        /* UI */
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

        /* Filters */
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

        /* Helpers */
        .text-adaptive { color: var(--text-main) !important; }
        .text-muted-adaptive { color: var(--text-muted) !important; }
        
        [data-theme="dark"] .text-dark { color: #fff !important; }
        [data-theme="dark"] .bg-white { background-color: #1e1e1e !important; }
        [data-theme="dark"] .border-bottom { border-color: #444 !important; }
        /* Dark mode inputs */
        [data-theme="dark"] .form-control { background-color: #333; border-color: #444; color: #fff; }

        footer {
            background-color: #1a1a1a;
            color: #ccc;
            padding: 40px 0;
            margin-top: 60px;
            border-top: 4px solid var(--emu-orange);
        }
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
                    <li class="nav-item"><a class="nav-link" href="#calendar"><i class="far fa-calendar-alt me-1"></i> Calendar</a></li>
                    <li class="nav-item"><a class="nav-link" href="#events"><i class="fas fa-list me-1"></i> Events</a></li>
                    <li class="nav-item">
                        <a href="#" onclick="alert('System Message: Admin Login Panel is under construction.')" class="btn btn-login btn-sm px-4 rounded-pill">
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

            <div class="col-lg-9" id="events">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0 text-adaptive">All Events</h3>
                </div>

                <div class="row" id="events-container">
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
                                    
                                    <a href="javascript:void(0);" onclick="downloadReport(this)" class="btn btn-outline-danger btn-sm w-100">
                                        <i class="fas fa-file-pdf me-2"></i>Download Event Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // --- 1. DARK MODE LOGIC ---
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

        // --- 2. FILTER & SEARCH LOGIC (COMBINED) ---
        // Trigger filter on Button Click OR Enter Key in search box
        const applyBtn = document.getElementById('apply-filters-btn');
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');

        function executeFilter() {
            // Get inputs
            const searchQuery = searchInput.value.toLowerCase();
            const selectedSDGs = Array.from(document.querySelectorAll('input[name="sdg"]:checked')).map(cb => cb.value);
            const selectedTHE = Array.from(document.querySelectorAll('input[name="the"]:checked')).map(cb => cb.value);
            const selectedQS = Array.from(document.querySelectorAll('input[name="qs"]:checked')).map(cb => cb.value);

            const events = document.querySelectorAll('.event-item');
            let visibleCount = 0;

            events.forEach(event => {
                // Get Event Data
                const eventSDG = event.getAttribute('data-sdg');
                const eventTHE = event.getAttribute('data-the');
                const eventQS = event.getAttribute('data-qs');
                const eventTitle = event.querySelector('.card-title').innerText.toLowerCase();
                const eventDesc = event.querySelector('.card-text').innerText.toLowerCase();

                // 1. Check Filters (If empty, match all)
                const matchSDG = selectedSDGs.length === 0 || selectedSDGs.includes(eventSDG);
                const matchTHE = selectedTHE.length === 0 || selectedTHE.includes(eventTHE);
                const matchQS = selectedQS.length === 0 || selectedQS.includes(eventQS);

                // 2. Check Search (If empty, match all)
                const matchSearch = (searchQuery === "") || 
                                    (eventTitle.includes(searchQuery) || eventDesc.includes(searchQuery));

                // Combine conditions
                if (matchSDG && matchTHE && matchQS && matchSearch) {
                    event.style.display = 'block';
                    visibleCount++;
                } else {
                    event.style.display = 'none';
                }
            });

            // Handle "No Results"
            const noResultsMsg = document.getElementById('no-results');
            if (visibleCount === 0) {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        }

        // Event Listeners
        applyBtn.addEventListener('click', executeFilter);
        searchBtn.addEventListener('click', executeFilter);
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                executeFilter();
            }
        });

        // --- 3. DOWNLOAD BUTTON LOGIC ---
        function downloadReport(btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
            btn.classList.add('disabled');
            
            setTimeout(() => {
                alert("Prototype: Report PDF has been downloaded successfully!");
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Downloaded';
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success', 'disabled');
                    btn.classList.add('btn-outline-danger');
                }, 3000);
            }, 1000);
        }
    </script>
</body>
</html>