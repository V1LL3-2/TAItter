# TAItter - The Future of Social Media

A modern, Twitter-like social media application built with PHP, MySQL, and vanilla JavaScript. TAItter allows users to share posts up to 144 characters, follow hashtags, like users, and discover content through an intelligent timeline.

## Features

### Core Functionality
- **User Registration & Authentication** - Secure user accounts with email and username
- **Post Creation** - Share thoughts in 144 characters or less
- **Hashtag Support** - Tag posts with #hashtags for better discoverability
- **User Mentions** - Mention other users with @username
- **Smart Timeline** - Personalized feed based on followed hashtags and liked users
- **User Profiles** - View user information, stats, and posts
- **Search** - Find users, posts, and hashtags
- **Settings** - Manage profile, followed hashtags, and liked users

### Advanced Features
- **Responsive Design** - Works on desktop, tablet, and mobile
- **Real-time Updates** - Auto-refresh feed every 30 seconds
- **Modern UI** - Clean, intuitive interface with smooth animations
- **Security** - CSRF protection, password hashing, input sanitization
- **Performance** - Optimized database queries and efficient data loading

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Styling**: Custom CSS with CSS Grid and Flexbox
- **Icons**: Font Awesome 6
- **Fonts**: Inter (Google Fonts)

## Installation

### Prerequisites
- XAMPP (or similar LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Setup Instructions

1. **Download and Install XAMPP**
   - Download XAMPP from https://www.apachefriends.org/
   - Install and start Apache and MySQL services

2. **Clone/Download the Project**
   - Place the `taitter` folder in your XAMPP `htdocs` directory
   - Path should be: `C:\xampp\htdocs\taitter\`

3. **Create the Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the `database/schema.sql` file
   - This will create the `taitter` database with all required tables and sample data

4. **Configure Database Connection**
   - The database configuration is in `config/database.php`
   - Default settings should work with XAMPP:
     - Host: localhost
     - Database: taitter
     - Username: root
     - Password: (empty)

5. **Start the Application**
   - Open your browser and navigate to `http://localhost/taitter`
   - You should see the TAItter homepage

## Default Login Credentials

The application comes with sample data including a default admin account:

- **Email**: admin@taitter.com
- **Password**: password

Additional sample users:
- john@example.com / password
- jane@example.com / password
- bob@example.com / password

## Project Structure

```
taitter/
├── api/                    # API endpoints
│   ├── auth.php           # Authentication (login/register/logout)
│   ├── posts.php          # Post management
│   ├── users.php          # User management
│   └── hashtags.php       # Hashtag management
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── app.js         # Main JavaScript
├── config/                # Configuration files
│   ├── config.php         # App configuration
│   └── database.php       # Database connection
├── database/              # Database files
│   └── schema.sql         # Database schema and sample data
├── models/                # Data models
│   ├── User.php           # User model
│   ├── Post.php           # Post model
│   ├── Hashtag.php        # Hashtag model
│   └── UserLike.php       # User like model
├── index.php              # Homepage
├── login.php              # Login page
├── register.php           # Registration page
├── profile.php            # User profile page
├── search.php             # Search page
├── hashtag.php            # Hashtag page
├── settings.php           # User settings page
└── README.md              # This file
```

## API Endpoints

### Authentication (`api/auth.php`)
- `POST` - Register new user
- `POST` - Login user
- `POST` - Logout user

### Posts (`api/posts.php`)
- `GET` - Get timeline/posts
- `POST` - Create new post
- `DELETE` - Delete post

### Users (`api/users.php`)
- `GET` - Get user profile/stats
- `POST` - Like user
- `DELETE` - Unlike user
- `PUT` - Update profile

### Hashtags (`api/hashtags.php`)
- `GET` - Get hashtags
- `POST` - Follow hashtag
- `DELETE` - Unfollow hashtag

## Database Schema

### Tables
- `users` - User accounts
- `posts` - User posts
- `hashtags` - Hashtag definitions
- `post_hashtags` - Post-hashtag relationships
- `post_mentions` - Post mention relationships
- `user_follows_hashtags` - User-hashtag follows
- `user_likes_users` - User-user likes

## Customization

### Styling
- Modify `assets/css/style.css` to change the appearance
- CSS variables are defined at the top for easy color scheme changes
- Responsive design breakpoints can be adjusted

### Functionality
- Add new features by extending the existing models
- Create new API endpoints in the `api/` directory
- Modify the frontend JavaScript in `assets/js/app.js`

## Security Features

- **Password Hashing** - Uses PHP's `password_hash()` function
- **Input Sanitization** - All user input is sanitized
- **CSRF Protection** - CSRF tokens for form submissions
- **SQL Injection Prevention** - Prepared statements throughout
- **XSS Protection** - Output escaping and content filtering

## Performance Optimizations

- **Database Indexing** - Proper indexes on frequently queried columns
- **Efficient Queries** - Optimized SQL queries with proper joins
- **Lazy Loading** - Sidebar data loaded asynchronously
- **Caching** - Session-based caching for user data

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check if MySQL is running in XAMPP
   - Verify database credentials in `config/database.php`
   - Ensure the `taitter` database exists

2. **Permission Errors**
   - Check file permissions on the project directory
   - Ensure Apache has read access to all files

3. **JavaScript Not Working**
   - Check browser console for errors
   - Ensure all files are properly linked
   - Verify JavaScript is enabled

4. **Styling Issues**
   - Clear browser cache
   - Check if CSS file is loading correctly
   - Verify Font Awesome CDN is accessible

### Debug Mode

To enable debug mode, modify `config/config.php`:
```php
// Change this line:
ini_set('display_errors', 1);
// To:
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support or questions, please create an issue in the project repository.

---

**TAItter** - The future of social media is here! 🚀
