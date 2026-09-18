# Symfony 8 TDD Pattern Guide - Working Implementation

Test-Driven Development (TDD) with Symfony 8.1, with a complete Todo management API with comprehensive test coverage.

---
## Why this Todos APP
I developed this project as an adherence to my contractual obligations, which restrict me from exposing any development URLs. To verify my identity and demonstrate my capabilities, I've dedicated some time to create this swift demonstration.

## Why TDD?
Test-Driven Development (TDD) is essential in the AI age to ensure the accuracy of algorithms, facilitate early detection of errors, and allow efficient code refactoring. This approach enhances the reliability of AI systems, making development more efficient and improving overall code quality. TDD also aids in creating a flexible codebase, allowing easier integration with future systems.

---

## Documentation Guides

### 01-tdd-todos.md - Complete TDD Implementation Guide for Todo API

A comprehensive step-by-step guide demonstrating Test-Driven Development with Symfony 8.1. This document covers all four phases of testing: Unit Tests for business logic, Integration Tests for database operations, API Tests for HTTP endpoints, and Smoke Tests for end-to-end workflows. Learn how to implement the complete Todo management system using the RED-GREEN-REFACTOR cycle, with detailed code examples for entities, services, repositories, and controllers. Perfect for understanding TDD best practices in a real Symfony application.

Link: [01-tdd-todos.md](01-tdd-todos.md)

---

### 02-rabbit-mq.md - RabbitMQ Message Queue Implementation with TDD

A hands-on guide for integrating RabbitMQ message queuing into the Symfony project using Test-Driven Development principles. This document walks through 15 implementation steps including composer package installation, environment configuration, creating a Publisher service and command, implementing a Worker command for message consumption, and manual integration testing. Features important learnings about Symfony auto-discovery constraints, queue locking issues, and message persistence on worker crashes. Includes complete code examples, testing procedures, and troubleshooting guidelines.

Link: [02-rabbit-mq.md](02-rabbit-mq.md)

---

### 03-redis-session.md - Redis Session Configuration with TDD

A practical guide for configuring Redis session storage in Symfony 8.1 using Test-Driven Development principles. This document covers 9 implementation steps organized in 5 phases: Session Service configuration with unit tests, Environment setup with functional tests, Test environment configuration, HTTP session handling with controller tests, and complete workflow integration tests. Includes detailed code examples for  Predis clients, environment variable injection, session lifecycle management, verification commands for Redis CLI, performance optimization options, and a production deployment checklist.

Link: [03-redis-session.md](03-redis-session.md)

---

### lint.md - Linting and Type Checking Configuration Guide

A comprehensive guide for configuring static analysis tools in your Symfony project. This document walks through setting up PHPStan for type checking, PHP-CS-Fixer for code style formatting, and Psalm for advanced type analysis. Covers installation, configuration, IDE integration, pre-commit hooks, and CI/CD setup. Includes practical workflows for daily development and gradual adoption strategies for existing codebases.

Link: [lint.md](lint.md)

---

### strict.md - Adding declare(strict_types=1) to All Files

A step-by-step guide for automatically adding `declare(strict_types=1);` to every PHP file in your project using PHP-CS-Fixer. Explains what strict types are, why they matter, and how they improve type safety. Covers dry-run previews, verification methods, common issues (wrong placement, duplicate declarations, new type errors), pre-commit hooks, and CI/CD integration. Includes troubleshooting for type mismatches that strict types may reveal.

Link: [strict.md](strict.md)

---

### fix-redis.md - Redis Session Configuration Issues and Solutions

Comprehensive troubleshooting guide for Redis session configuration problems. Documents the "locking option not supported" error when using Predis client, explains why it occurs, and provides three solutions: removing locking (recommended for Predis), using native Redis extension, or implementing custom locking. Includes detailed code examples, testing procedures, and comparison table of Redis clients and their capabilities.

Link: [fix-redis.md](fix-redis.md)

---

### fix.md - PHPStan Static Analysis Error Fixes

Comprehensive guide for resolving PHPStan type checking errors. Documents 4 specific errors found during static analysis with detailed explanations, root causes, and multiple solution options for each. Covers WorkerCommand prefetch_size type mismatch, TodoController array_map callback type issues, unused Kernel method, and TodoRepository missing generic types. Includes complete code examples showing before/after fixes, best practices for type hints and PHPStan configuration, and verification procedures.

Link: [fix.md](fix.md)

