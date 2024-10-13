# 🪵 codex

[![ci](https://github.com/syntatis/codex/actions/workflows/ci.yml/badge.svg)](https://github.com/syntatis/codex/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/syntatis/codex/graph/badge.svg?token=9Y9PU6IOA8)](https://codecov.io/gh/syntatis/codex)

> [!CAUTION]
> This package is currently under active development. It is not recommended for production use.

A codebase designed to build WordPress extensions with modern PHP practices.

## Why?

WordPress is a powerful platform, but while PHP has evolved over the years, WordPress development has largely stayed the same. Modern PHP practices like Autoloading with [Composer](https://getcomposer.org) and Dependency Injection aren't commonly used when building WordPress extensions. It has caused some gaps between WordPress and the rest of the PHP ecosystem.

This project aims to close the gap by providing functions, classes, and structure as the foundation to build WordPress extensions with modern PHP techniques.

## Projects

The following is a list of projects that are built on top of **Codex** as the foundation.

- 🧪 👋 [howdy](https://github.com/syntatis/howdy): A WordPress plugin boilerplate with modern development tools, easier configuration, and an improved folder structure.
- 🧪 ✨ [howdy-open-ai](https://github.com/syntatis/howdy-open-ai): A WordPress plugin boilerplate with OpenAI PHP client add-in.

## Providers

Providers are classes that provide additional services in the service container.

- 🧪 🎛 [`codex-settings-provider`](https://github.com/syntatis/codex-settings-provider): Service provider for WordPress Settings API

## Inspiration

This project is inspired by the following awesome projects in the PHP ecosystem:

- [Illuminate: The Laravel Components](https://github.com/illuminate)
- [Symfony: Reusable PHP components](https://github.com/symfony)
