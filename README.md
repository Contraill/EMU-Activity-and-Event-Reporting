# EMU Activity & Event Reporting Portal - learning project

A comprehensive web-based system for managing, tracking, and reporting university activities and events aligned with UN Sustainable Development Goals (SDGs), THE Impact Rankings, and QS Stars evaluation criteria.

## 📋 Overview

This portal enables Eastern Mediterranean University to systematically document activities, track SDG contributions, and generate compliance reports for international accreditation standards.

**🌍 Bilingual Support**: Full English/Turkish language switching with separate data sources for localized content.

## ✨ Features

### Public Portal ([index.php](index.php))
- 📅 Interactive event calendar widget
- 🔍 Advanced filtering (SDGs, THE criteria, QS criteria)
- � **EN/TR language toggle** with automatic content switching
- 🌓 Dark/Light theme toggle with localStorage persistence
- 🔐 Staff login modal with role-based redirects
- 📄 Client-side PDF report generation (jsPDF)
- ✨ **Smooth animations** (hero fade-in, navbar scroll shadow, hover effects)
- 🏷️ **Event badges** (TODAY/COMPLETED) with time-based logic
- 🌍 **Localized SDG/THE tags** with Turkish translations
- 📱 Fully responsive Bootstrap 5 design

### User Dashboard ([user_dashboard.php](user_dashboard.php))
- 💾 Save events for later viewing
- ✅ Register for events
- 📊 **Attended events tracker** (registered + past events count)
- 📥 Download registered event reports
- 🔔 Upcoming events widget
- 🌐 **EN/TR language switching** with dynamic content loading
- 🚫 **Auto-hide expired events** from browse section
- ✨ Same animations and badges as public portal

### Admin Dashboard ([admin_dashboard.php](admin_dashboard.php))
- ➕ Add/Edit/Delete events (CRUD operations)
- 💾 **Dual JSON storage** (events.json + eventstr.json)
- 📝 **Bilingual form fields** (Title EN/TR, Description EN/TR)
- 🔄 **Automatic sync** between English and Turkish datasets
- 🏷️ Multi-select SDG, THE, and QS category tagging
- 📈 Coverage analytics and KPI tracking
- 📊 CSV export with UTF-8 BOM (Excel-compatible)
- 📄 Full administrative PDF report generation
- 📋 Event management table
- 🌐 **EN/TR language toggle** with UI translations
- ✨ Smooth animations and conditional success messages

### Calendar View ([calendar.php](calendar.php))
- 🗓️ Interactive month/year navigation
- 🎯 Day highlighting for events
- 📌 Today indicator
- 🔍 Click-to-filter event list by date
- 🌐 **Localized weekdays/months** (Mon-Sun / Pzt-Paz)
- 🏷️ **TODAY/ENDED badges** with Turkish translations
- 🚫 **"All Upcoming" filter** excludes expired events
- ✨ Consistent animations across all pages

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ (Session-based state management)
- **Frontend**: HTML5, CSS3 (Custom CSS Variables), JavaScript (ES6+)
- **UI Framework**: Bootstrap 5.3.0
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Google Fonts (Merriweather, Open Sans)
- **PDF Generation**: jsPDF 2.5.1 + autoTable plugin
- **Data Storage**: Dual JSON files ([events.json](events.json) for English, [eventstr.json](eventstr.json) for Turkish)
- **State Management**: localStorage for theme and language preferences

## 📂 Project Structure

```
EMU-Activity-and-Event-Reporting/
├── index.php                 # Public portal (homepage)
├── user_dashboard.php        # Student/Staff user interface
├── admin_dashboard.php       # Administrative panel
├── calendar.php              # Interactive calendar view
├── events.json               # Event database (English)
├── eventstr.json             # Event database (Turkish)
├── README.md                 # Documentation (this file)
└── images/
    └── logo.ico              # Favicon
```

## 🚀 Installation

### Prerequisites
- XAMPP/WAMP/LAMP (Apache + PHP 7.4+)
- Modern web browser (Chrome, Firefox, Edge)

### Setup Steps

1. **Clone or download** this repository to your web server directory:
   ```bash
   cd c:\xampp\htdocs\
   git clone <repository-url> EMU-Activity-and-Event-Reporting
   ```

2. **Verify file permissions** (Linux/Mac):
   ```bash
   chmod 644 events.json
   chmod 755 *.php
   ```

3. **Start Apache** via XAMPP Control Panel

4. **Access the portal**:
   ```
   http://localhost/EMU-Activity-and-Event-Reporting/
   ```

5. **Default login credentials**:
   - **Admin**: `admin` / `admin`
   - **User**: `user` / `user`

## 📖 Usage Guide

### For Administrators

1. **Login** via Staff Login button → Enter admin credentials
2. **Switch Language**: Click EN/TR button to toggle interface language
3. **Add Event**:
   - Fill out bilingual form (Title EN, Title TR, Description EN, Description TR)
   - Add date, time, organizer, location, and contact info
   - Select multiple SDGs, THE, and QS categories via checkboxes
   - Submit to **automatically save to both** [events.json](events.json) and [eventstr.json](eventstr.json)
4. **Edit Event**: Click edit icon (✏️) → Update both EN and TR fields → Syncs both files
5. **Delete Event**: Click trash icon (🗑️) → Removes from both JSON files with confirmation
6. **Export Data**: Click "Export CSV" for Excel-compatible file
7. **Generate Report**: Click "Generate Full Report" for PDF

### For Users

1. **Browse Events**: View all events on homepage
2. **Filter**: Use sidebar filters (SDGs, THE, QS) + search box
3. **Save Event**: Click "Save" button to bookmark
4. **Register**: Click "Register" to unlock report downloads
5. **Download Report**: Click "Download Report" (PDF opens in browser)

### For Visitors

- Browse events without login
- **Switch language** (EN/TR) to view localized content
- Use filters to find relevant activities (SDG/THE/QS)
- View upcoming calendar widget with TODAY badges
- Access interactive calendar page with localized dates
- Dark/Light theme toggle for comfortable viewing

## 🎨 Customization

### Theme Colors (CSS Variables)

Edit in `<style>` section of each file:

```css
:root {
    --emu-dark-blue: #002147;   /* Primary brand color */
    --emu-light-blue: #005A9C;  /* Accent blue */
    --emu-orange: #F58220;      /* Highlight color */
}
```

### SDG / Criteria Lists

Modify arrays in PHP files:

```php
// In admin_dashboard.php or index.php
$sdg_list = ["SDG 1: ...", "SDG 2: ...", ...];
$the_list = ["Teaching", "Research", ...];
$qs_list = ["Teaching & Learning", ...];
```

## 📊 Data Model

### Event Object Structure

**English** ([events.json](events.json))

```json
{
    "id": 1,
    "title": "Event Title",
    "date": "2025-12-15",
    "time": "14:30",
    "description": "Event details...",
    "sdg": "SDG 14: Life Below Water",
    "the_cat": "Research",
    "qs_cat": "Research Quality",
    "organizer": "Department Name",
    "image": "https://...",
    "location": "Venue",
    "participants": "120",
    "status": "Completed",
    "contact": "email@emu.edu.tr"
}
```

**Turkish** ([eventstr.json](eventstr.json))

```json
{
    "id": 1,
    "title": "Etkinlik Başlığı (Turkish)",
    "date": "2025-12-15",
    "time": "14:30",
    "description": "Etkinlik detayları (Turkish)...",
    "sdg": "SDG 14: Life Below Water",
    "the_cat": "Research",
    "qs_cat": "Research Quality",
    "organizer": "Bölüm Adı",
    "image": "https://...",
    "location": "Mekan",
    "participants": "120",
    "status": "Completed",
    "contact": "email@emu.edu.tr"
}
```

**Note**: SDG/THE/QS values remain in English keys for consistency; frontend handles translation display.

## 🔒 Security Notes

⚠️ **This is a prototype system**. For production use:

1. Replace hardcoded login with database authentication
2. Implement password hashing (bcrypt/Argon2)
3. Add CSRF token protection for forms
4. Validate/sanitize all user inputs
5. Use PDO/MySQLi for database (not plain JSON)
6. Implement proper session management
7. Add file upload validation for images
8. Use HTTPS for production deployment

## 🐛 Known Limitations

- No database backend (uses JSON file)
- Simple authentication (hardcoded credentials)
- No user registration system
- No email notifications
- Limited to single JSON file (scalability)
- No image upload interface (URL-based only)


## 📝 License

This is a university prototype project for educational purposes.

---

**Built with ❤️ for EMU Community**
