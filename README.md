# Employee Management API

The project implements a REST API for batch import and employee data management.

## Technology Stack

* PHP 8.2+
* Symfony 7.0
* MySQL
* Doctrine ORM

## Installation and Setup

### Prerequisites

* PHP 8.2 or higher
* Composer
* MySQL

### Installation Steps

1. Clone the repository
```bash
git clone <repository-url>
cd employee-management-api
```

2. Install dependencies
```bash
composer install
```

3. Create `.env.local` file and configure database connection
```bash
# .env.local
DATABASE_URL="mysql://username:password@127.0.0.1:3306/employees?serverVersion=8.0.32&charset=utf8mb4"
```

4. Create database and run migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Start the built-in server
```bash
symfony server:start
```

Or use your preferred web server (Apache, Nginx).

## API Endpoints

### Import Employees from CSV

```
POST /api/employee
Content-Type: text/csv
```

Example:
```bash
curl -X POST -H 'Content-Type: text/csv' --data-binary @import.csv http://localhost:8000/api/employee
```

### Get List of All Employees

```
GET /api/employee
GET /api/employee?page=1&limit=20

Example in terminal: curl -X GET https://127.0.0.1:8000/api/employee?page=1&limit=100

```

### Get Specific Employee Details

```
GET /api/employee/{emp_id}

Example in terminal: curl -X GET https://127.0.0.1:8000/api/employee/133641


```

### Delete Employee

```
DELETE /api/delete/employee/{emp_id}

Example in terminal: curl -X DELETE https://127.0.0.1:8000/api/employee/133641

```

## Project Structure

- `src/Entity/Employee.php` - Employee data model
- `src/Repository/EmployeeRepository.php` - Repository for employee data operations
- `src/Controller/EmployeeController.php` - API endpoint controller
- `src/Service/CsvImportService.php` - Service for CSV data import
