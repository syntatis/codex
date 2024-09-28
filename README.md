# 🪵 codex

[![ci](https://github.com/syntatis/codex/actions/workflows/ci.yml/badge.svg)](https://github.com/syntatis/codex/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/syntatis/codex/graph/badge.svg?token=9Y9PU6IOA8)](https://codecov.io/gh/syntatis/codex)

> [!CAUTION]
> This package is currently under active development. It is not recommended for production use.

A codebase designed to build WordPress extensions with modern PHP practices.

## Why?

WordPress is a powerful platform, but while PHP has evolved over the years, WordPress development has largely stayed the same. Modern PHP practices like Autoloading with [Composer](https://getcomposer.org) and Dependency Injection aren't commonly used when building WordPress extensions. This situation has caused some gaps between WordPress and the rest of the PHP ecosystem.

This project aims to close that gap by providing functions, classes, and structure as the foundation to build WordPress extensions with modern PHP techniques.

## Projects

The following is a list of projects that are built on top of the **Codex**:

- [howdy](https://github.com/syntatis/howdy) 🚧: A starter kit to develop a WordPress plugin with some common (modern) PHP practices.


## Providers

Providers are classes that provide services to the Codex application. They are registered with the application and can be accessed via the application's container.

- [`codex-assets-provider`](https://github.com/syntatis/codex-assets-provider) 🚧: Provides a way to enqueue scripts and styles in WordPress.

## Inspiration

This project is inspired by the following awesome projects in the PHP ecosystem:

- [Illuminate: The Laravel Components](https://github.com/illuminate)
- [Symfony: Reusable PHP components](https://github.com/symfony)

