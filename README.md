# Grey Shot: One Truth Per Day

An anonymous social sharing platform where users can share one truth per day, fostering authentic and meaningful connections through honest storytelling.

## 🌟 Features

### Core Concept
- **One Truth Per Day**: Users can post only one "truth" per day
- **Anonymous Identity**: Users have random, anonymous usernames (e.g., "MysticWave123")
- **Reading Requirement**: Must read others' truths before posting again
- **Community Engagement**: Upvote and reaction system to build connections

### User System
- **Anonymous Registration**: No personal information required
- **Random Usernames**: Automatically generated unique usernames
- **Profile Icons**: Notion-style avatar system (no real photos)
- **Session Management**: Secure login/logout functionality

### Posting & Interaction
- **Daily Truth Limit**: One post per day with exceptions:
  - Can post another truth if you react to 3 more posts after your last post
  - Can delete your previous truth to post a new one
  - First truth of the day has no reading requirement if no other truths exist
- **Upvote System**: Reddit-like upvoting for posts
- **Reaction System**: Three meaningful reactions:
  - 😌 "I relate"
  - 💡 "I needed this"
  - 🙏 "Thank you"
- **Reading History**: Tracks which posts users have read

### Browse Experience
- **Guest Browsing**: Can view posts without logging in
- **Interactive Features**: Login required for posting, upvoting, and reacting
- **Responsive Design**: Works seamlessly on desktop and mobile
- **Clean UI**: Minimalist, distraction-free interface

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Server**: PHP Development Server (or Apache/Nginx)

## 📁 Project Structure

```
PHP final project/
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   ├── images/                # Static images
│   └── js/
│       └── main.js           # Frontend JavaScript
├── includes/
│   ├── auth.php              # Authentication functions
│   ├── config.php            # Database configuration
│   ├── delete_post.php       # Post deletion handler
│   ├── mark_read.php         # Reading history handler
│   ├── post_interactions.php # Upvotes and reactions
│   ├── submit_comment.php    # Comment submission (legacy)
│   └── submit_post.php       # Post submission handler
├── database_v2.sql           # Current database schema
├── database.sql              # Legacy database schema
├── test_data.sql             # Sample data for testing
├── index.php                 # Main application page
├── login.php                 # Login page
├── register.php              # Registration page
├── logout.php                # Logout handler
├── manage.php                # Post management (legacy)
└── README.md                 # This file
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, or PHP development server)

### Step 1: Clone the Project
```bash
git clone <repository-url>
cd "PHP final project"
```

### Step 2: Database Setup
1. Create a MySQL database:
```sql
CREATE DATABASE grey_shot;
```

2. Import the database schema:
```bash
mysql -u username -p grey_shot < database_v2.sql
```

3. (Optional) Load test data:
```bash
mysql -u username -p grey_shot < test_data.sql
```

### Step 3: Configure Database Connection
Edit `includes/config.php` with your database credentials:
```php
<?php
$host = 'localhost';
$dbname = 'grey_shot';
$username = 'your_username';
$password = 'your_password';
```

### Step 4: Run the Application
Using PHP development server:
```bash
php -S localhost:8000
```

Or configure with Apache/Nginx and access via your web server.

### Step 5: Access the Application
Open your browser and navigate to:
- `http://localhost:8000` (PHP dev server)
- Or your configured web server URL

## 📊 Database Schema

### Tables Overview
- **users**: User accounts with anonymous usernames and profile data
- **posts**: Daily truths with voting and interaction tracking
- **reactions**: User reactions to posts (relate, needed, thank you)
- **upvotes**: Post upvoting system
- **reading_history**: Tracks which posts users have read
- **rate_limits**: IP-based rate limiting (legacy)
- **comments**: Comment system (legacy feature)

### Key Relationships
- Users can have multiple posts, reactions, upvotes, and reading history entries
- Posts belong to users and can have multiple reactions and upvotes
- Reading history tracks user engagement with posts

## 🎯 Usage Guide

### For New Users
1. **Browse**: Visit the homepage to read existing truths
2. **Register**: Click "Login/Register" to create an anonymous account
3. **Read Truths**: Explore what others have shared
4. **Share Your Truth**: Post your daily truth when ready
5. **Engage**: Upvote and react to posts that resonate with you

### Posting Rules
- **First Truth**: Can post immediately if no other truths exist for the day
- **Subsequent Truths**: Must either:
  - React to 3 more posts since your last post, OR
  - Delete your previous truth for the day
- **Daily Limit**: Maximum one active truth per day

### Interaction Types
- **Upvote**: Show appreciation for a truth
- **React**: Choose from three meaningful reactions
- **Read**: Automatically tracked when viewing posts

## 🔧 Development

### Adding Features
1. Database changes go in `database_v2.sql`
2. PHP logic in appropriate `includes/` files
3. Frontend updates in `assets/js/main.js` and `assets/css/style.css`

### Testing
- Use `test_data.sql` for sample data
- Test with multiple users and different scenarios
- Verify rate limiting and posting rules
