# 🏘️ Hello Neighbor

**Hello Neighbor** is a secure, lightweight community management portal designed to foster communication and organization within residential buildings or local neighborhoods. It provides a centralized hub for private messaging, public announcements, community voting, and document management.



---

## 🌟 Key Features

### 🔐 Security & Identity
* **Secure Authentication:** Full user lifecycle management including registration, login, and session-based logout.
* **Email Verification:** Prevents bot registrations by requiring account activation via `verify.php`.
* **Account Recovery:** Robust, token-based password reset workflow with a 1-hour expiration safety window.
* **Modern Encryption:** Uses PHP `password_hash()` for credentials and PDO prepared statements to block SQL Injection.
* **CSRF Protection:** Integrated security tokens on all state-changing forms (Feed, Polls, Profile).

### 💬 Social & Communication
* **Interactive Feed:** A multi-tab notice board (Public/Private/Other) with date-based filtering and pagination.
* **Private Messaging:** A 1-on-1 inbox system with real-time "unread" message counts in the navigation bar.
* **Neighbor Directory:** A searchable list of residents where users can find neighbors by name or shared skills/summaries.

### 🗳️ Governance & Resources
* **Community Polls:** Admin-controlled polling system featuring single-vote enforcement via database constraints.
* **Event Calendar:** A localized calendar allowing residents to RSVP ("Count Me In") to neighborhood gatherings.
* **Document Vault:** Secure repository for house rules and legal files, featuring unique filename hashing to prevent direct URL guessing.

---

## 🛠️ Technical Stack

* **Backend:** PHP 8.x (Singleton Database pattern)
* **Database:** MySQL / MariaDB (Relational schema with `ON DELETE CASCADE` integrity)
* **Frontend:** Vanilla JavaScript, HTML5, CSS3 (Custom design system using CSS Variables)
* **Localization:** Support for English (`en`) and Bulgarian (`bg`)



---

## 📂 Project Structure

| File | Description |
| :--- | :--- |
| `index.php` | The main router and layout controller for the app. |
| `database.php` | PDO Connection Singleton and `.env` parser. |
| `styles.css` | Global responsive design system and utility classes. |
| `feed.php` | Notice board logic with categorization and pagination. |
| `messages.php` | Private messaging interface and conversation threads. |
| `polls.php` | Community voting frontend and admin creation tools. |
| `profile.php` | User dashboard with account settings and "Danger Zone" deletion. |
| `verify.php` | Backend logic for email token validation. |

---


## 🎨 UI/UX Design
The application features a modern "Inter" font stack and a clean, card-based interface. It is fully responsive, optimized for both mobile browsing and desktop management.

## 📝 License & Notes
* **Project Type:** Unofficial Learning Web App.
* **Timezone:** Defaulted to `Europe/Sofia`.
* **Developer:** Filip Peev

---
*“Bridging the gap between front doors.”*