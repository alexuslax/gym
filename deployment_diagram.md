# UEP Fitness Gym Management System - Deployment Diagram

## Deployment Architecture

```mermaid
graph TB
    subgraph "Client Layer"
        Browser1[Web Browser<br/>Admin/Staff]
        Browser2[Web Browser<br/>Member]
        Browser3[Web Browser<br/>Trainer]
        RFID[RFID Reader<br/>Hardware Device]
    end

    subgraph "Web Server Layer"
        Apache[Apache HTTP Server<br/>Port 80/443<br/>XAMPP]
    end

    subgraph "Application Layer"
        PHP[PHP Application<br/>7.x/8.x]
        
        subgraph "PHP Modules"
            Auth[Authentication<br/>login.php<br/>logout.php]
            Admin[Admin Views<br/>dashboard.php<br/>members.php<br/>attendance.php<br/>billing.php]
            Member[Member Views<br/>dashboard.php<br/>attendance.php<br/>billing.php<br/>profile.php]
            Trainer[Trainer Views<br/>dashboard.php<br/>members.php<br/>schedule.php]
            Config[Configuration<br/>database.php<br/>functions.php]
        end
    end

    subgraph "Data Layer"
        MySQL[(MySQL Database<br/>gym_management<br/>Port 3306)]
        
        subgraph "Database Tables"
            Users[users]
            Members[members]
            Attendance[attendance]
            Billing[billing]
            Trainers[trainers]
            Equipment[equipment]
            Logs[system_logs]
        end
    end

    subgraph "Storage Layer"
        FileSystem[File System<br/>htdocs/gym/]
        
        subgraph "File Storage"
            Images[img/<br/>Profile Pictures<br/>Logos]
            CSS[css/<br/>Stylesheets]
            JS[js/<br/>JavaScript Files]
        end
    end

    %% Client to Web Server connections
    Browser1 -->|HTTP/HTTPS| Apache
    Browser2 -->|HTTP/HTTPS| Apache
    Browser3 -->|HTTP/HTTPS| Apache
    RFID -->|POST Request| Apache

    %% Web Server to Application connections
    Apache -->|Processes PHP| PHP
    PHP --> Auth
    PHP --> Admin
    PHP --> Member
    PHP --> Trainer
    PHP --> Config

    %% Application to Database connections
    Config -->|PDO Connection| MySQL
    Auth -->|Query| MySQL
    Admin -->|Query| MySQL
    Member -->|Query| MySQL
    Trainer -->|Query| MySQL

    %% Database internal structure
    MySQL --> Users
    MySQL --> Members
    MySQL --> Attendance
    MySQL --> Billing
    MySQL --> Trainers
    MySQL --> Equipment
    MySQL --> Logs

    %% Application to Storage connections
    PHP -->|Read/Write| FileSystem
    FileSystem --> Images
    FileSystem --> CSS
    FileSystem --> JS

    %% Styling
    classDef clientLayer fill:#e1f5ff,stroke:#01579b,stroke-width:2px
    classDef webLayer fill:#fff3e0,stroke:#e65100,stroke-width:2px
    classDef appLayer fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    classDef dataLayer fill:#e8f5e9,stroke:#1b5e20,stroke-width:2px
    classDef storageLayer fill:#fce4ec,stroke:#880e4f,stroke-width:2px

    class Browser1,Browser2,Browser3,RFID clientLayer
    class Apache webLayer
    class PHP,Auth,Admin,Member,Trainer,Config appLayer
    class MySQL,Users,Members,Attendance,Billing,Trainers,Equipment,Logs dataLayer
    class FileSystem,Images,CSS,JS storageLayer
```

## Current Deployment Environment

### Development Environment (XAMPP)
- **Web Server**: Apache (via XAMPP)
- **Application Server**: PHP (via XAMPP)
- **Database Server**: MySQL (via XAMPP)
- **Host**: localhost
- **Port**: 80 (HTTP)
- **Database Port**: 3306

### Technology Stack
- **Backend**: PHP 7.x/8.x
- **Database**: MySQL (gym_management)
- **Frontend**: HTML5, CSS3, JavaScript
- **CSS Framework**: Tailwind CSS (CDN)
- **Session Management**: PHP Sessions
- **Database Access**: PDO (PHP Data Objects)

## Component Descriptions

### Client Layer
- **Web Browsers**: Access the application via HTTP/HTTPS
- **RFID Reader**: Hardware device for member check-in/check-out

### Web Server Layer
- **Apache HTTP Server**: Handles HTTP requests and serves static files
- **PHP Module**: Processes PHP scripts server-side

### Application Layer
- **Authentication Module**: Handles user login/logout and session management
- **Admin Views**: Dashboard, members management, attendance, billing, equipment, trainers, vitals, progress
- **Member Views**: Dashboard, attendance, billing, profile, progress, vitals
- **Trainer Views**: Dashboard, members, attendance, schedule, progress, profile
- **Configuration**: Database connection and utility functions

### Data Layer
- **MySQL Database**: Stores all application data
- **Tables**: users, members, attendance, billing, trainers, equipment, system_logs, and more

### Storage Layer
- **File System**: Stores static assets (images, CSS, JavaScript files)
- **Profile Pictures**: User and member profile images
- **Static Assets**: CSS stylesheets and JavaScript files

## Deployment Notes

### Current Setup
- Single-server deployment (all components on localhost via XAMPP)
- Development environment configuration
- Database credentials: root user with no password (default XAMPP)

### Production Considerations
1. **Security**: Change default database credentials
2. **HTTPS**: Enable SSL/TLS certificates
3. **Database**: Separate database server for better performance
4. **Load Balancing**: Multiple web servers for high availability
5. **Backup**: Regular database and file backups
6. **Monitoring**: Application and server monitoring tools
7. **Firewall**: Configure firewall rules for database access

## Network Flow

1. **User Request**: Browser sends HTTP request to Apache
2. **Request Processing**: Apache forwards PHP requests to PHP interpreter
3. **Application Logic**: PHP executes application code
4. **Database Query**: PHP uses PDO to query MySQL database
5. **Response Generation**: PHP generates HTML response
6. **Response Delivery**: Apache sends response back to browser
7. **File Serving**: Static files (CSS, JS, images) served directly by Apache

## Port Configuration

- **HTTP**: Port 80
- **HTTPS**: Port 443 (if SSL configured)
- **MySQL**: Port 3306
- **Apache**: Port 80/443

