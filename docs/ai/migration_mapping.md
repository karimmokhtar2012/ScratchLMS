# Migration Mapping

This document tracks the file movements during the refactoring to Domain-Driven Design (DDD) like structure.

## Controllers
| Old Path | New Path | Namespace |
|---|---|---|
| `app/Controllers/*` | `app/Http/Controllers/*` | `App\Http\Controllers` |

## Middleware
| Old Path | New Path | Namespace |
|---|---|---|
| `app/Middleware/*` | `app/Http/Middleware/*` | `App\Http\Middleware` |

## Models / Domain
| Old Path | New Path | New Namespace |
|---|---|---|
| `app/Models/Course.php` | `app/Domain/Courses/Course.php` | `App\Domain\Courses` |
| `app/Models/Module.php` | `app/Domain/Courses/Module.php` | `App\Domain\Courses` |
| `app/Models/Lesson.php` | `app/Domain/Lessons/Lesson.php` | `App\Domain\Lessons` |
| `app/Models/User.php` | `app/Domain/Users/User.php` | `App\Domain\Users` |
| `app/Models/Order.php` | `app/Domain/Orders/Order.php` | `App\Domain\Orders` |
| `app/Models/OrderItem.php` | `app/Domain/Orders/OrderItem.php` | `App\Domain\Orders` |
| `app/Models/Coupon.php` | `app/Domain/Orders/Coupon.php` | `App\Domain\Orders` |
| `app/Models/Notification.php` | `app/Domain/Notifications/Notification.php` | `App\Domain\Notifications` |
| `app/Models/QuestionBank.php` | `app/Domain/Quizzes/QuestionBank.php` | `App\Domain\Quizzes` |
| `app/Models/BankQuestion.php` | `app/Domain/Quizzes/BankQuestion.php` | `App\Domain\Quizzes` |

## Core / Policies
| Old Path | New Path | New Namespace |
|---|---|---|
| `app/Core/Authorization.php` | `app/Policies/Authorization.php` | `App\Policies` |

## Other
`app/Services` remains in `app/Services` but namespaces might need adjustment if they import models.
`app/Core` (Router, Controller, Database) remains in `app/Core`.
