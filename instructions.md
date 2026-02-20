Coding Instructions and Best Practices
General Guidelines
    1. Architecture & Quality:
        ◦ Plan as a software engineer.
        ◦ Privilege architecture quality and object-oriented design.
        ◦ Employ XP (Extreme Programming) paradigm (simplicity, feedback, courage, respect).
    2. Maintainability:
        ◦ Write solid, maintainable code.
        ◦ Avoid useless complexity
    3. Clarification:
        ◦ If analysis is not clear, ASK for more information.
        ◦ Do not fill in gaps without asking. Suggest options and ask for clarification.
    4. Security & Boundaries:
        ◦ NEVER access or edit files in the project's parent folders without explicit permission.
    5. Clean Code:
        ◦ Strive for clean architecture.
        ◦ Follow CodeIgniter 4 framework best practices.
        ◦ Follow general coding best practices (PSR standards for PHP).
    6. Application security:
        ◦  Ask for the datatype of parameters. Cast the parameter. If it cannot be cast, or has other other apparent errors, log the issue in the error log. Sanitize parameters when using them in the database with mysql_escape where appropriate. Look for sql-injection and xss attack vectors in the parameters.
        ◦ 
CSS Specific Rules
    1. Selectors:
        ◦ Use parent classes to style children.
        ◦ Example: Instead of adding a class to <li> (e.g. <li class="my-li">), add a class to <ul> and reference it as .my-ul > li or .my-ul li.
    2. Variables:
        ◦ Use CSS variables (var(--color-name), var(--font-name)) for colors and fonts when repeated more than once.
Environment
The project runs on a local nginx server with php 8.5 and responds at the url: https://faster.test/utils/
This folder is located at /home/fasterjoomla/public_html/utils/ in the filesystem.
All requests should be relative to this url, assuming it can change across installations. No reference to the url nor the absolute paths should ever enter the code. If an absolute path or an absolute url are necessary, add them to the configuration and reference them by code.
The database is mariadb, use the mariadb executable instead of mysql.
You can read the database configuration from the config.php file
