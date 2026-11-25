# LASU Smart Class Hall Scheduler

A multi-page PHP + MySQL class hall scheduling and resource management
system for the Lagos State University (LASU) Department of Computer
Science. It has a public student side (no login) and a secure admin side
(login required). Both sides share one MySQL database, so anything the
admin changes shows up on the student side immediately.

## Technology
PHP 8 (PDO), MySQL, HTML, CSS and vanilla JavaScript. No frameworks.

## Project structure
```
config/database.php     - database connection, auto-creates/upgrades tables, creates the admin account
includes/               - shared functions, auth guard, header/footer templates
assets/css/style.css    - all styling
assets/js/script.js     - menus, dropdowns, delete confirmations, search tabs, image preview
database/schema.sql     - reference copy of the database schema
uploads/halls/          - uploaded hall photos

Student pages (no login):
index.php, schedule.php, halls.php, hall-details.php,
availability.php, search.php, announcements.php

Admin pages (login required):
admin/login.php, dashboard.php, halls.php, courses.php, lecturers.php,
schedules.php, announcements.php, activity-log.php, change-password.php, logout.php
```

## Student side
- **Dashboard:** greeting and date, stats (Total Classes, Today's Classes,
  Available Halls), upcoming classes and quick links.
- **Schedule:** one week at a time with real dates, showing classes by
  day with their level.
- **Class Halls / Hall Details:** each hall's photo, capacity, location,
  status, resources and upcoming schedule.
- **Hall Availability:** each hall's status and which days of the current
  week it is in use.
- **Search:** halls, courses and lecturers.
- **Announcements:** published announcements with priority.

## Admin side
- **Dashboard:** Total Halls, Today's Classes, Total Bookings, Total
  Resources, today's schedule, hall availability and recent bookings.
- **Halls:** add, edit and delete halls, including photo, resources and
  status.
- **Courses and Lecturers:** add, edit and delete.
- **Class Schedule:** create, edit and delete scheduled classes, with
  conflict checking.
- **Announcements:** add, edit, publish and delete.
- **Change Password:** available from the profile menu.

Everything shown comes from the database. Nothing is hardcoded and no
demo data is created; the only record the app creates by itself is the
admin account. Halls, lecturers, courses, schedules and announcements
exist only once the admin adds them.

## Admin account
The system has a single admin (no user management or roles). The admin
can change the password from the profile menu under **Change Password**.
This requires the current password, and the new password must be at least
8 characters, must match its confirmation and must differ from the old one.

## Schedules and dates
- Every class is booked for one specific date. The admin picks a Date and
  the weekday is worked out from it. A class on Saturday Oct 31 does not
  appear again the next Saturday; each date needs its own schedule.
- Admins cannot schedule a class on a date that has already passed.
- Students can see this week, upcoming weeks, and last week as a
  read-only record. Days that have already passed this week are no longer
  listed, and anything older than last week is hidden.
- Dashboard, Hall Details and Upcoming Classes show today onward only.
- Availability dots reflect the current week only.
- The system uses Lagos time (Africa/Lagos), so "today" is correct.
- Schedules created before dates were required have no date. They are
  hidden from students and flagged "No date, edit to set" in the admin
  table.

## Conflict detection
When a schedule is created or edited with "Check for Conflicts" ticked,
the system blocks the save and shows an error if:
- the same hall is already booked on that exact date in an overlapping
  time range, or
- the same lecturer is already teaching another class on that date in an
  overlapping time range.

## Announcements
When adding an announcement the admin chooses how long it stays up: 1 day, 1 week, 2 weeks, 3 weeks or 1 month. After that time it disappears from the student page automatically (Lagos time). When editing, the admin can keep the current expiry or restart the timer from now. The admin table shows each announcement's expiry and marks expired ones. Older announcements created before this feature have no expiry and keep showing until unpublished or deleted.

## Hall status
Status is automatic. A hall shows **In Use** while a class is running in it right now and **Available** otherwise. The admin can switch a hall to **Under Maintenance**, which blocks it off until changed back. Classes that are cancelled never make a hall busy.

## Weekly repeat
When creating a schedule the admin can set "Repeat weekly until" a date (up to 26 weeks). The same class is booked every week on the same day, each date checked for conflicts. If any date conflicts, nothing is saved and the clashing date is shown.

## Cancelled and moved classes
- **Cancel class** marks a class as Cancelled. Students still see it (struck through with a Cancelled badge) but it does not count in totals or block the hall. **Restore** brings it back after a conflict check.
- Changing the hall of an existing class marks it "Moved from [old hall]" for students.

## Now and starting-soon badges
Classes happening today show a **Now** badge while running and **Starts in N min** during the hour before they begin.

## Find a Free Hall
Students pick a date and a time range and see every hall that is free then (halls under maintenance and halls with a class in that range are left out).

## Class level
When scheduling a class the admin chooses its level: 100, 200, 300 or 400 Level. Students see the level in the Schedule table, on Hall Details and on the dashboard's upcoming classes. Classes created before this option show a dash until they are edited and given a level.

## Activity log
Every admin action (adding, editing, deleting, cancelling, logging in, changing the password) is recorded and listed on the admin Activity Log page.

## Clearing old data
The admin can delete classes older than last week (Schedules page) and expired announcements (Announcements page) with one button each.

## Hall images
- The admin uploads a JPG, PNG or WebP photo of up to 2 MB when adding or
  editing a hall.
- The file is verified by its actual content (not just its extension),
  renamed to a random server-generated name and stored in `uploads/halls/`.
  Its path is saved in the `halls.image_path` column.
- The photo appears on the admin hall list, the student Class Halls cards
  and the Hall Details page, always read from the database.
- A hall without a photo shows a large fallback area on Hall Details and
  nothing extra on the Class Halls cards, so there is never a broken
  image.

## Resources
Resources are a single field on each hall in Manage Halls, for example
`Projector x2, Whiteboard, AC x3`, with items separated by commas. They
appear as an icon grid under "Resources" on that hall's Hall Details page,
and the admin dashboard's Total Resources counts these items. There is no
separate resources menu, and students do not see a resources stat.

## Security
- Login lockout: after 5 wrong passwords the sign-in is blocked for 15 minutes.
- A new admin account must choose its own password the first time it logs in.
- All database queries use PDO prepared statements, so there is no SQL
  injection surface.
- All output is escaped with `htmlspecialchars()`, which prevents XSS.
- Passwords are hashed with `password_hash()` and checked with
  `password_verify()`, never stored or compared in plain text.
- Every admin form (login, add, edit, delete, change password) carries a
  CSRF token that is checked on submit.
- Security headers (`X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy` and a `Content-Security-Policy`) are sent on every
  page.
- Uploaded images are validated by content, size-limited and given random
  names; no script file can be written to the uploads folder.
- Every admin page checks the login session and redirects to the login
  page if it is missing.

## Notes
- The admin sidebar has Dashboard, Class Schedule, Halls, Courses,
  Lecturers, Announcements and Logout.
- Students reach Availability, Search and Announcements through the More
  menu on mobile.
- The layout is responsive and works from phone to desktop screens.
