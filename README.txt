Games für die Freigeister – Apache/PHP community site

Files:
- index.php: protected main game page
- login.php: login page
- logout.php: logout endpoint
- admin.php: admin-only entry point
- community-api.php: ratings/comments API
- community.css: light styling
- community-data/.htaccess: protects JSON data

Requirements:
- Apache with PHP 8+ enabled.
- No database and no mbstring extension are required.

The admin user can delete individual comments and individual votes from the community section.

Permissions:
Make community-data writable by the Apache/PHP user, e.g. on Debian/Ubuntu:
  chown -R www-data:www-data community-data
  chmod 750 community-data

Important: change the supplied passwords before using this site publicly.
