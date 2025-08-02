# 🪵 codex

[![ci](https://github.com/syntatis/codex/actions/workflows/ci.yml/badge.svg)](https://github.com/syntatis/codex/actions/workflows/ci.yml) 
[![codecov](https://codecov.io/gh/syntatis/codex/graph/badge.svg?token=9Y9PU6IOA8)](https://codecov.io/gh/syntatis/codex)
![Packagist Dependency Version](https://img.shields.io/packagist/dependency-v/syntatis/codex/php?color=7a86b8)

> [!CAUTION]
> This project is still in development and currently tagged as `v0.*`, which means it's not stable yet and may include breaking changes between versions. I keep working toward a stable release, but until then, things may change as we improve the project. Thanks for your interest and feel free to explore, test, or contribute!

## Why?

WordPress is a powerful platform, but while PHP has evolved over the years, WordPress development has largely stayed the same. Modern PHP practices like Autoloading with [Composer](https://getcomposer.org) and Dependency Injection aren't commonly used when building extensions for WordPress. It has caused some gaps between WordPress and the rest of the PHP ecosystem.

This project aims to close the gap by providing functions, classes, and structure as the foundation to build extensions for WordPress with a slightly modern PHP approach.

## Providers

* 🧪 🎛 [`codex-settings-provider`](https://github.com/syntatis/codex-settings-provider): [WordPress® Settings API](https://developer.wordpress.org/plugins/settings/settings-api/) service provider

## Inspiration

This project is inspired by the following awesome projects in the PHP ecosystem:

- [Illuminate: The Laravel Components](https://github.com/illuminate)
- [Symfony: Reusable PHP components](https://github.com/symfony)
