# Forge Starter: Your Minimal Forge Framework Base

Welcome to Forge Starter! This project is designed to be the **lightest possible starting point** for building web applications with the Forge PHP framework. It includes only the essential components to get you up and running quickly, keeping your project lean and efficient.

## What's Included?

Forge Starter comes pre-configured with the following core Forge framework modules:

*   **ForgePackageManager:** The Forge package manager itself! Use this to easily install and manage additional modules to expand your project's functionality.
*   **ForgeErrorHandler:** Provides robust error and exception handling, giving you helpful error pages during development and graceful error handling in production.
*   **ForgeViewEngine:** A fast and flexible view engine for creating dynamic HTML templates.
*   **ForgeRouter:** A powerful router to define your application's routes and handle incoming web requests.

This minimal setup gives you a solid foundation for building web applications while keeping your project size incredibly small (under 500KB!). Perfect for projects where performance, simplicity, and a clear understanding of your codebase are key.

## Installation

Getting started with Forge Starter is easy:

1.  **Clone this repository:**
    ```bash
    git clone [repository-url] your-project-name
    cd your-project-name
    ```
    *(Replace `[repository-url]` with the actual URL of your Forge Starter repository and `your-project-name` with your desired project directory name.)*

2.  **Install dependencies using ForgePackageManager:**
    ```bash
    php forge.php package:install
    ```
    This command will read the `forge.lock.json` file (or `forge.json` if it's a fresh project) and install the necessary Forge framework components and any modules listed in your `forge.json` manifest.  This sets up your project with the Forge core and the modules included in Forge Starter.

3.  **Set up your environment:**
    *   **Environment variables:** Copy `.env.example` to `.env` and configure your application's environment variables (database settings, application URL, etc.) in the `.env` file.
    *   **Generate application key (optional but recommended for security):**
        ```bash
        php forge.php key:generate
        ```
        *(This command generates a unique application key and sets it in your `.env` file.  This is important for security, especially in production environments.)*

4.  **Serve your application (for development):**
    You can use the built-in Forge development server:
    ```bash
    php forge.php serve
    ```
    This will start a development server, by default serving your application at `http://localhost:8080`. You can customize the host and port:
    ```bash
    php forge.php serve 127.0.0.1 9090
    ```
    This will serve your application at `http://127.0.0.1:9090`.

    Alternatively, you can use PHP's built-in web server directly:
    ```bash
    php -S localhost:8000 -t public
    ```
    Then, access your application in your browser at `http://localhost:8000`.

## 💡 Adding More Modules

One of the strengths of Forge is its modularity. You can easily extend your Forge Starter project by installing additional modules using the ForgePackageManager.

To install a new module, use the `forge package:require` command:

```bash
php forge.php package:require [module-name]
```

(Replace [module-name] with the name of the Forge module you want to install, e.g., forge package:require ForgeDatabaseModule. You can find a list of available modules at the [Forge Modules Repository](https://github.com/forge-engine/modules).)

### Other Useful Forge Commands:
The Forge CLI tool provides several commands to help you manage your project:

| Command                               | Description             |
|---------------------------------------|-------------------------|
| `php forge.php install:module <name>` | Install a module        |
| `php forge.php remove:module <name>`  | Remove a module         |
| `php forge.php list:modules`          | Show installed modules  |
| `php forge.php config:cache`          | Caches your application's configuration |
| `php forge.php config:clear`          | Clears the cached configuration      |
| `php forge.php make:module`           | Creates a new Forge module with a basic directory structure      |
| `php forge.php publish module-name --type=config,views,components,assets,all`               | Publishes resources (like configuration files, assets, views)      |



- You can always see the full list of available commands and their descriptions by running:

```bash
php forge.php help
```

## License

Forge Engine, Modules, Starter are open-source software licensed under the [MIT license](LICENSE).

---

📚 [Full Documentation](https://forge-engine.github.io/) |
🐛 [Report Issues](https://github.com/forge-engine/forge-starter/issues) |
💡 [Feature Requests](https://github.com/forge-engine/forge/discussions)

_Forge Framework - Build explicitly, scale infinitely_