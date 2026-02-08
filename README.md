# Hello Neighbor

## 1. Introduction
Hello Neighbor is a community-driven notice board application designed for residents of a building or neighborhood. It facilitates communication through public and private feeds, document sharing, and interactive polling.

## 2. System Overview
The system is a web-based platform built with a PHP backend and a MariaDB database. It utilizes a custom `.env` configuration for environment-specific settings and an automated installation script for deployment.

## 3. Functional Requirements

### 3.1 User Management
- **Authentication**: Users can register and log in to access member-only content.
- **Role-Based Access Control (RBAC)**:
  - **Guest**: Can view the Public Info feed.
  - **Member**: Can post, edit, and delete their own notices; participate in polls; and comment on posts.
  - **Admin**: Full control over all content, including deleting any post/comment, creating polls, and managing building documents.

### 3.2 Notice Feed (Current Module)
- **Categorization**: Notices are divided into "Public Info", "Private Board", and "Other Information".
- **Date Filtering**: Users can navigate the feed by date using a calendar picker or "Previous/Next" buttons.
- **Intelligent Empty State**: If no posts exist for a selected date, the system suggests the most recent date containing activity.
- **CRUD Operations**: Members can create, read, update, and delete their text-based notices.

### 3.3 Interactive Features
- **Commenting System**: Users can engage in threaded discussions on any notice.
- **Polling**: Admins can create community polls with multiple options.
- **Document Repository**: A central location for residents to view or download important building documents (e.g., house rules).

## 4. Technical Requirements

- **Backend**: PHP 8+
- **Database**: MySQL/MariaDB with PDO for secure, prepared-statement queries.
- **Security**:
  - Transaction handling for complex data entry (e.g., creating polls).
  - Server-side validation for user permissions on every action.
  - Physical file cleanup when document records are deleted.

## 5. User Interface (UI)

- **Responsive Design**: The application uses a mobile-first approach with a navigation bar and container-based layout.
- **Multilingual Support**: The system supports English and Bulgarian languages via session-based localization.