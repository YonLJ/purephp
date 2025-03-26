# Installation

This guide will help you install and configure PurePHP in your project.

## Requirements

- PHP 7.4 or higher
- Composer

## Installation via Composer

1. Create a new project or navigate to your existing project:

```bash
mkdir my-purephp-app
cd my-purephp-app
```

2. Initialize Composer if you haven't already:

```bash
composer init
```

3. Install PurePHP:

```bash
composer require purephp/purephp
```

## Manual Installation

1. Download the latest release from GitHub
2. Extract the files to your project
3. Add the autoloader to your entry file:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';
```

## Configuration

1. Create a configuration file `config.php`:

```php
<?php

return [
    'debug' => true,
    'cache' => false,
    'charset' => 'UTF-8'
];
```

2. Load the configuration in your entry file:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

// Initialize PurePHP with config
Pure\init($config);
```

## Directory Structure

```
my-purephp-app/
├── composer.json
├── composer.lock
├── config.php
├── public/
│   ├── index.php
│   ├── style.css
│   └── js/
│       └── app.js
├── src/
│   ├── components/
│   │   ├── Navigation.php
│   │   └── Card.php
│   └── layouts/
│       └── Main.php
└── vendor/
```

## Development Setup

1. Clone the repository:

```bash
git clone https://github.com/yourusername/purephp.git
cd purephp
```

2. Install dependencies:

```bash
composer install
```

3. Run tests:

```bash
composer test
```

## Next Steps

- [Quick Start](/en/guide/getting-started) - Get started with PurePHP
- [Core Concepts](/en/guide/concepts) - Understand the core concepts
- [Components](/en/guide/components) - Learn about component development
