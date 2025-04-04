# 🔥 Forge Starter: Your Barebones Forge Playground!

Hey there! 👋 Welcome to Forge Starter – think of this as your super lightweight starting kit for building web apps with the Forge PHP framework. It's got just the essentials to get you going quickly, so your project stays nice and lean.

## What's Inside This Kit?

Forge Starter comes with a couple of cool things pre-installed:

- **ForgePackageManager:** This is the Forge package manager itself! You'll use this to easily add and manage any extra modules you want in your project.
- **ForgeErrorHandler:** This helps you handle errors and exceptions like a pro. During development, it'll show you helpful error pages, and in production, it'll make sure things fail gracefully.

This minimal setup means your project starts super small (under 240KB!). It's perfect if you're all about speed, simplicity, and knowing exactly what's going on in your code.

## Let's Get This Show on the Road! (Installation)

Getting started with Forge Starter is a piece of cake:

1.  **Grab a copy of this project:**

    ```bash
    git clone [repository-url] your-project-name
    cd your-project-name
    ```

    _(Just replace `[repository-url]` with the actual link to this Forge Starter repository, and `your-project-name` with whatever you want to name your project folder.)_

2.  **Install the basics using ForgePackageManager:**

    ```bash
    php install.php
    php forge.php package:install-project
    ```

    This command will look at the `forge.lock.json` file (or `forge.json` if it's a brand new project) and install the main Forge framework bits and any modules listed in your `forge.json` file. This gets your project set up with the core of Forge and the stuff that comes with the starter.

3.  **Tweak your settings:**

    - **Environment stuff:** Copy the `.env.example` file to `.env` and open up `.env` to set things like your database info and your website's URL.
    - **Make it secure (optional, but a good idea!):**
      ```bash
      php forge.php key:generate
      ```
      _(This command creates a unique key for your app and puts it in your `.env` file. It's especially important for security when your site goes live.)_

4.  **See your app in action (for testing):**
    You can use Forge's built-in mini-server:

    ```bash
    php forge.php serve
    ```

    This will start a little server for you, usually at `http://localhost:8080`. You can even change the address and port if you want:

    ```bash
    php forge.php serve 127.0.0.1 9090
    ```

    That'll make it run at `http://127.0.0.1:9090`.

    Or, if you prefer, you can use PHP's own built-in server directly:

    ```bash
    php -S localhost:8000 -t public
    ```

    Then just open your browser and go to `http://localhost:8000`.

## 💡 Want More Features? Add Modules!

One of the cool things about Forge is that it's super modular. You can easily add more features to your Forge Starter project by installing extra modules using the ForgePackageManager.

To add a new module, just run this command:

```bash
php forge.php package:install-module module-name@[module-version]
```

(Replace [module-name] with the name of the Forge module you want to install, e.g., forge package:require ForgeDatabaseModule. You can find a list of available modules at the [Forge Modules Repository](https://github.com/forge-engine/modules).)

### Other Useful Forge Commands:

The Forge command-line tool has a bunch of useful commands to help you manage your project:

| Command                                       | Description                                                 |
| --------------------------------------------- | ----------------------------------------------------------- |
| `php forge.php package-install:module <name>` | Install a module                                            |
| `php forge.php package:remove-module <name>`  | Remove a module                                             |
| `php forge.php package:list-modules`          | Show installed modules                                      |
| `php forge.php clear:cache`                   | Clears the cache views, classes                             |
| `php forge.php clear:log`                     | Clears the logs                                             |
| `php forge.php make:module`                   | Creates a new Forge module with a basic directory structure |

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
