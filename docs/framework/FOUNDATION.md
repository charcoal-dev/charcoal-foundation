# Charcoal Monolith Foundation

Charcoal Monolith Foundation operates as a Level 0 framework, providing both:
- **An HTTP server entrypoint**
- **A CLI engine**

Below are the primary namespaces included in this framework:

| Namespace               | Directory                                        | Description                                                               |
|-------------------------|--------------------------------------------------|---------------------------------------------------------------------------|
| `App\Shared\*`          | [src/shared](../../src/shared)                   | Core modules and classes that the application inherits from the framework |
| `App\Domain\*`          | [src/app](../../src/domain)                         | Additional modules and classes used by the application                    |
| `App\Services\Engine\*` | [src/services/engine](../../src/interfaces/engine) | Classes specifically designed for CLI scripts and processes               |
| `App\Services\Web\*`    | [src/services/web](../../src/interfaces/web)       | Controllers and other classes needed for HTTP-facing operations           |

## Services and Access

Below is a brief look at the available services and the namespaces they can utilize:

| Service | Type | Namespaces Available                                        |
|---------|------|-------------------------------------------------------------|
| engine  | CLI  | `App\Shared\*`, `App\Domain\*`, and `App\Services\Engine\*` |
| web     | HTTP | `App\Shared\*`, `App\Domain\*`, and `App\Services\Web\*`    |

