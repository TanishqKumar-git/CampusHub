# CampusHub – A Student Community & Resource-Sharing Platform

A Reddit-inspired web app where students from colleges across India connect through college communities, discuss academics, share notes and previous-year papers, and discover internships, hackathons, scholarships, events and placements.

## Features

- **Accounts:** register, login, logout (sessions, hashed passwords)
- **Communities:** one per college, join / leave with AJAX, member counts, "All Colleges" feed
- **Posts:** create, edit, delete; tags (Academics, Doubt, Project, General); sort by New / Top
- **Comments:** flat comments on every post
- **Upvotes / downvotes:** AJAX, one vote per user, click again to remove
- **Resources:** upload and download PDF / DOC / DOCX / PPT / PPTX / ZIP (max 10 MB), filter by subject, semester, college and type, download counter
- **Opportunities:** internships, hackathons, scholarships, events, placements with category filter and "Expired" badge; users can suggest, admin approves
- **Profiles:** bio, college, joined communities, own posts and uploads, edit profile
- **Search:** posts, communities and resources
- **Admin page:** add / approve / delete opportunities and see basic stats
- **Coming soon:** teammate finder (placeholder card)

## Tech Stack

HTML5, CSS3, JavaScript (vanilla, fetch API), PHP 8 (PDO), MySQL, XAMPP

## Setup (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy this folder into `C:\xampp\htdocs\` (or `/opt/lampp/htdocs/`) and name it **campushub**.
3. Open `http://localhost/phpmyadmin` → **Import** → choose `schema.sql` → **Go**.
   This creates the `campushub` database with sample data.
4. Copy `config/db.sample.php` to `config/db.php` (if it does not exist) and check the MySQL username and password (XAMPP default: `root`, empty password).
5. Make sure the `uploads/` folder is writable (on Linux: `chmod 775 uploads`).
6. Open **http://localhost/campushub/**

## Test accounts

| Role  | Email                | Password |
|-------|----------------------|----------|
| Admin | admin@campushub.com  | admin123 |
| User  | rahul@example.com    | user123  |
| User  | ananya@example.com   | user123  |

Sample resources are demo rows without real files. Upload your own file to test downloads.

## Folder structure

```
campushub/
├── config/          db.php (ignored by Git), db.sample.php
├── includes/        init.php, functions.php, auth.php, header.php, footer.php
├── auth/            login.php, register.php, logout.php
├── pages/           home, community, post, post_form, delete_post, resources,
│                    download, opportunities, profile, edit_profile, search
├── api/             vote.php, join_community.php  (JSON endpoints)
├── admin/           opportunities.php
├── assets/          css/style.css, js/main.js, img/
├── uploads/         uploaded files (ignored by Git)
├── schema.sql
└── index.php
```

## Security

- PDO prepared statements everywhere (SQL injection)
- `htmlspecialchars` on all output (XSS)
- CSRF token on every POST form and AJAX request
- Passwords stored with `password_hash`, `session_regenerate_id` on login
- Upload whitelist by extension and real MIME type, random file names, PHP execution disabled in `uploads/`
- Role checks on admin pages; only owners or admins can edit or delete content

## Screenshots

_Add screenshots here (home feed, community page, resources, opportunities)._

## Author

Add your name here.
