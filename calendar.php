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

        .hero-section {
            background: linear-gradient(rgba(0, 33, 71, 0.9), rgba(0, 33, 71, 0.8)), url('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Eastern_Mediterranean_University_Rectorate_Building.jpg/1200px-Eastern_Mediterranean_University_Rectorate_Building.jpg');
            background-position: center;
            background-size: cover;
            color: white;
            padding: 60px 0;
            border-bottom: 5px solid var(--emu-orange);
        }

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
            <a class="navbar-brand" href="index.php">
                <img src="https://www.emu.edu.tr/static/images/logos/emu-logo-horizontalwhite-tr.svg" alt="EMU Logo">
            </a>
            
            <button class="navbar-toggler navbar-dark border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php"> <i class="fas fa-home me-1"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#"><i class="far fa-calendar-alt me-1"></i> Calendar</a></li>
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
            <h1 class="display-5 fw-bold mb-3">Event Calendar</h1>
            <p class="lead mb-0" style="opacity: 0.9;">
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
                            <small onclick="jumpToToday()" style="cursor:pointer; text-decoration:underline; opacity:0.8;">Go to Today</small>
                        </div>
                        <button class="btn-nav" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>

                    <div class="weekdays">
                        <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
                    </div>

                    <div class="days" id="daysGrid">
                        </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="m-0" style="color: var(--text-main);">Events</h5>
                    <span class="badge bg-secondary" id="selectedDateLabel">All Upcoming</span>
                </div>
                
                <div id="eventListContainer">
                    <?php if(empty($events)): ?>
                        <div class="alert alert-warning">No events found in database.</div>
                    <?php else: ?>
                        <?php foreach($events as $event): ?>
                        <div class="event-list-item rounded">
                            <h6 class="fw-bold mb-1" style="color: var(--text-main);"><?php echo $event['title']; ?></h6>
                            <small class="d-block mb-1" style="color: var(--text-muted);">
                                <i class="far fa-clock me-1"></i> <?php echo date('M d, Y', strtotime($event['date'])); ?>
                            </small>
                            <span class="badge bg-success" style="font-size: 0.7rem;"><?php echo $event['sdg']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
        // 1. Pass PHP Data to JS
        const eventsData = <?php echo json_encode($events); ?>;

        const date = new Date();
        let currYear = date.getFullYear();
        let currMonth = date.getMonth(); // 0-11

        const monthYearDisplay = document.getElementById("monthYearDisplay");
        const daysGrid = document.getElementById("daysGrid");
        const prevBtn = document.getElementById("prevMonth");
        const nextBtn = document.getElementById("nextMonth");

        const months = ["January", "February", "March", "April", "May", "June", "July",
                        "August", "September", "October", "November", "December"];

        // 2. Render Calendar Function
        const renderCalendar = () => {
            let firstDayOfMonth = new Date(currYear, currMonth, 1).getDay(); // 0(Sun) - 6(Sat)
            // Adjust for Mon-Sun week start (Standard in TR/EU)
            // JS returns Sun=0, Mon=1. We want Mon=0, Sun=6.
            firstDayOfMonth = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;

            let lastDateOfMonth = new Date(currYear, currMonth + 1, 0).getDate(); // 30, 31 etc.
            let lastDayOfMonth = new Date(currYear, currMonth, lastDateOfMonth).getDay();
            let lastDateOfLastMonth = new Date(currYear, currMonth, 0).getDate();
            
            let liTag = "";

            // Show previous month's last days
            for (let i = firstDayOfMonth; i > 0; i--) {
                liTag += `<div class="prev-date">${lastDateOfLastMonth - i + 1}</div>`;
            }

            // Show current month's days
            for (let i = 1; i <= lastDateOfMonth; i++) {
                // Check if today
                let isToday = i === date.getDate() && currMonth === new Date().getMonth() && 
                              currYear === new Date().getFullYear() ? "today" : "";
                
                // Format date string YYYY-MM-DD to match JSON
                let monthStr = (currMonth + 1).toString().padStart(2, '0');
                let dayStr = i.toString().padStart(2, '0');
                let dateString = `${currYear}-${monthStr}-${dayStr}`;

                // Check for Events
                let hasEvent = eventsData.some(e => e.date === dateString) ? "has-event" : "";

                liTag += `<div class="${isToday} ${hasEvent}" onclick="filterEventsByDate('${dateString}')">${i}</div>`;
            }

            // Show next month's first days to fill grid
            // Calculate how many cells needed to complete the grid (usually 35 or 42 cells)
            // But simple fill:
            let remainingCells = 42 - (firstDayOfMonth + lastDateOfMonth); 
            // Usually simpler just to fill until end of week row
            
            // Just basic next days filler
            for (let i = 1; i <= 6; i++) {
                 // Logic to stop creating rows can be complex, keeping it simple:
                 // if grid isn't full looking, add some next days.
                 // For this prototype, we just stop here or add a few if row is empty.
            }

            monthYearDisplay.innerText = `${months[currMonth]} ${currYear}`;
            daysGrid.innerHTML = liTag;
        }

        // 3. Navigation Logic
        prevBtn.addEventListener("click", () => {
            currMonth--;
            if(currMonth < 0) {
                currMonth = 11;
                currYear--;
            }
            renderCalendar();
        });

        nextBtn.addEventListener("click", () => {
            currMonth++;
            if(currMonth > 11) {
                currMonth = 0;
                currYear++;
            }
            renderCalendar();
        });

        function jumpToToday() {
            currYear = new Date().getFullYear();
            currMonth = new Date().getMonth();
            renderCalendar();
            document.getElementById('selectedDateLabel').innerText = "All Upcoming";
            renderEventList(eventsData); // Reset list
        }

        // 4. Click Day -> Filter List
        function filterEventsByDate(dateStr) {
            const filteredEvents = eventsData.filter(e => e.date === dateStr);
            document.getElementById('selectedDateLabel').innerText = dateStr;
            renderEventList(filteredEvents);
        }

        function renderEventList(data) {
            const container = document.getElementById('eventListContainer');
            if (data.length === 0) {
                container.innerHTML = '<div class="alert alert-light border">No events on this day.</div>';
                return;
            }

            let html = "";
            data.forEach(event => {
                html += `
                <div class="event-list-item rounded">
                    <h6 class="fw-bold mb-1" style="color: var(--text-main);">${event.title}</h6>
                    <small class="d-block mb-1" style="color: var(--text-muted);">
                        <i class="far fa-clock me-1"></i> ${event.time}
                    </small>
                    <span class="badge bg-success" style="font-size: 0.7rem;">${event.sdg}</span>
                    <div class="mt-2">
                        <small class="text-muted" style="font-size:0.75rem;">${event.organizer}</small>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }

        // Initial Render
        renderCalendar();

        // --- Dark Mode Logic (Shared) ---
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
    </script>
</body>
</html>