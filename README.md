## Super-Kernel DI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/super-kernel/di.svg?style=flat-square)](https://packagist.org/packages/super-kernel/di)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-~8.4.0-8892bf.svg?style=flat-square)](_https://php.net_)

**Super-Kernel DI** is a specialized Dependency Injection container designed for the super-kernel framework.

### 💡 Core Philosophy

This container strictly adheres to [PSR-11](https://www.php-fig.org/psr/psr-11/) but introduces a specific management
strategy to ensure performance and architectural clarity:

- Long-lived Object Management: The container is exclusively responsible for managing objects that persist throughout
  the
  application lifecycle (e.g., singletons, shared services, configuration).
- Caller Autonomy: In accordance with the PSR-11 meta-document, short-lived objects (request-specific or transient
  objects) should be managed by the caller, not the container. This prevents the "Service Locator" antipattern and
  keeps the container overhead minimal.

### 🛠️ Requirements

- PHP: ^8.4 (utilizing the latest engine features and property hooks)
- Framework: [super-kernel](https://github.com/super-kernel) core

### 🚀 Installation

Install the package via [Composer](https://getcomposer.org/):

```bash
composer require super-kernel/di
```

### 📝 Key Features

- [x] PSR-11 Compliant: Fully compatible with any standards-compliant library.
- [x] Modern PHP Stack: Leveraging PHP 8.4's strict typing and performance optimizations.
- [x] Lightweight Footprint: Focused on long-lived service resolution to reduce memory consumption.
- [x] Alpha Status: Under active development, open for architectural feedback.

### 📖 Quick Usage

As a component of the super-kernel ecosystem, the DI container is typically initialized by the framework kernel. For
manual usage:

```php
use SuperKernel\Context\ApplicationContext;
use SuperKernel\Contract\ApplicationInterface;

ApplicationContext::getContainer()->get(ApplicationInterface::class)->run();
```

### ⚠️ Project Status

This project is currently in its Alpha stage:

- The API is unstable and subject to breaking changes.
- Production use is not recommended at this time.
- We encourage developers to experiment and provide feedback via Issues or Pull Requests.

### 🤝 Contributing

Contributions are what make the open-source community an amazing place to learn, inspire, and create. Any contributions
you make are greatly appreciated.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### ⚖️ License

Distributed under the **MIT License**.Copyright © 2024-2026 **super-kernel**.

See the [LICENSE](https://github.com/super-kernel/di/blob/main/LICENSE) file for more information.
