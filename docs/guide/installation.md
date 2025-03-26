# 安装

本指南将帮助你安装和配置 PurePHP。

## 环境要求

- PHP 7.4 或更高版本
- Composer 2.0 或更高版本
- Web 服务器（Apache、Nginx 等）

## 安装步骤

### 1. 使用 Composer 安装

```bash
composer require purephp/purephp
```

### 2. 配置自动加载

在你的 `composer.json` 文件中，确保已经配置了自动加载：

```json
{
    "require": {
        "purephp/purephp": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

然后运行：

```bash
composer dump-autoload
```

### 3. 创建入口文件

创建 `public/index.php` 文件：

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use function Pure\HTML\{div, h1, p};

// 创建页面
div(
    h1('欢迎使用 PurePHP'),
    p('安装成功！')
)->class('container')->toPrint();
```

### 4. 配置 Web 服务器

#### Apache 配置

创建 `.htaccess` 文件：

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

#### Nginx 配置

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/your/project/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 项目结构

推荐的项目结构如下：

```
my-purephp-app/
├── composer.json
├── composer.lock
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── src/
│   ├── components/
│   ├── layouts/
│   └── pages/
├── vendor/
└── README.md
```

## 开发服务器

你可以使用 PHP 内置的开发服务器来运行项目：

```bash
php -S localhost:8000 -t public
```

然后在浏览器中访问 `http://localhost:8000`。

## 配置选项

### 1. 设置时区

在 `public/index.php` 文件开头添加：

```php
date_default_timezone_set('Asia/Shanghai');
```

### 2. 错误报告

开发环境：

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

生产环境：

```php
error_reporting(0);
ini_set('display_errors', 0);
```

### 3. 会话配置

```php
session_start();
```

## 常见问题

### 1. 安装失败

如果安装过程中遇到问题，请确保：

- PHP 版本符合要求
- Composer 已正确安装
- 网络连接正常
- 有足够的磁盘空间

### 2. 自动加载问题

如果遇到类找不到的问题，请检查：

- `composer.json` 中的自动加载配置
- 命名空间是否正确
- 是否运行了 `composer dump-autoload`

### 3. 权限问题

确保 Web 服务器用户对以下目录有写入权限：

- `vendor/`
- `public/assets/`

## 下一步

- [快速开始](/guide/getting-started) - 创建你的第一个 PurePHP 应用
- [基本概念](/guide/concepts) - 了解 PurePHP 的核心概念
- [组件](/guide/components) - 学习如何创建和使用组件
