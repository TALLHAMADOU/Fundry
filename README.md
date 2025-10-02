# Fundry

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hamadou/fundry.svg?style=flat-square)](https://packagist.org/packages/hamadou/fundry)
[![Total Downloads](https://img.shields.io/packagist/dt/hamadou/fundry.svg?style=flat-square)](https://packagist.org/packages/hamadou/fundry)

A comprehensive Laravel package for managing virtual wallets, transactions, and multiple currencies. Fundry provides elegant APIs and powerful Artisan commands to handle both cash and cryptocurrency wallets, with built-in export capabilities for PDF and Excel reports.

## Table of Contents

1. [About The Project](#about-the-project)
2. [Features](#features)
3. [Requirements](#requirements)
4. [Installation](#installation)
5. [Configuration](#configuration)
6. [Usage](#usage)
7. [Artisan Commands](#artisan-commands)
8. [API Reference](#api-reference)
9. [Troubleshooting](#troubleshooting)
10. [Support](#support)
11. [Contributing](#contributing)
12. [Security](#security)
13. [Credits](#credits)
14. [License](#license)

## About The Project

Fundry solves the complex problem of financial management in Laravel applications by providing a simple, elegant wallet system. Whether you're building an e-commerce platform, payment gateway, or financial tracking application, Fundry offers the tools you need for robust money management.

### Key Capabilities

- Multi-currency wallet management
- Transaction tracking and history
- Cash and cryptocurrency support
- Financial reporting and exports
- Configurable limits and restrictions

## Features

- 💰 **Virtual Wallet Management** - Create and manage cash and crypto wallets
- 💱 **Multi-Currency Support** - Handle multiple currencies with exchange rates
- 📊 **Transaction Management** - Complete transaction history and tracking
- 📄 **Export Capabilities** - Generate PDF and Excel reports
- ⚙️ **Configurable Limits** - Set max balances, daily limits, and restrictions
- 🎨 **Laravel Integration** - Built on Eloquent models and Laravel conventions
- 🛠️ **Artisan Commands** - Powerful CLI tools for management
- 🔒 **Security First** - Built with financial security in mind

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- BCMath PHP Extension
- JSON PHP Extension

## Installation

### 1. Install via Composer

```bash
composer require hamadou/fundry