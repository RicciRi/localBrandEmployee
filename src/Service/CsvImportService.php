<?php

namespace App\Service;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmployeeRepository $employeeRepository,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     *
     * @param string $csvContent
     * @return array
     */
    public function importEmployeesFromCsv(string $csvContent): array
    {
        $stats = [
            'total' => 0,
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // logs
        $logPath = __DIR__ . '/../../var/log/import.log';
        $debugLogPath = __DIR__ . '/../../var/log/import_debug.log';
        $skippedRowsPath = __DIR__ . '/../../var/log/skipped_rows.log';

        file_put_contents($logPath, "Import started " . date('Y-m-d H:i:s') . "\n");
        file_put_contents($debugLogPath, "Debug started " . date('Y-m-d H:i:s') . "\n");
        file_put_contents($skippedRowsPath, "Skipped rows " . date('Y-m-d H:i:s') . "\n");

        // Check raw data
        $rawLineCount = substr_count($csvContent, "\n");
        file_put_contents($logPath, "Raw line count: $rawLineCount\n", FILE_APPEND);

        // Get headers
        $headers = $this->parseCsv($csvContent);
        $columnMap = $this->mapColumns($headers);

        // Check for required columns
        $requiredColumns = ['Emp ID', 'First Name', 'Last Name'];
        foreach ($requiredColumns as $requiredCol) {
            if (!in_array($requiredCol, $headers)) {
                throw new \Exception("Missing required column: $requiredCol");
            }
        }

        // Get direct database connection
        $conn = $this->entityManager->getConnection();

        // Begin a general transaction
        $conn->beginTransaction();

        try {
            $rowCount = 0;
            $processedRows = 0;

            foreach ($this->parseCsvRows($csvContent, $headers) as $index => $rowData) {
                $rowCount++;
                $stats['total']++;

                // Extended logging
                $debugDetails = [
                    'row_number' => $rowCount,
                    'emp_id' => $rowData[$columnMap['empId']] ?? 'N/A',
                    'data_length' => strlen(implode(',', $rowData)),
                ];

                try {
                    // Skip empty
                    if (empty(array_filter($rowData))) {
                        $stats['skipped']++;
                        file_put_contents($skippedRowsPath, "Empty row #$rowCount\n", FILE_APPEND);
                        continue;
                    }

                    if (!$this->validateRowData($rowData, $columnMap)) {
                        $stats['failed']++;
                        file_put_contents($skippedRowsPath, "Invalid row #$rowCount: " . json_encode($rowData) . "\n", FILE_APPEND);
                        continue;
                    }

                    // Check for ID
                    $empId = $rowData[$columnMap['empId']] ?? null;
                    if (!$empId) {
                        throw new \Exception('Missing employee ID');
                    }

                    $params = $this->prepareEmployeeData($rowData, $columnMap);

                    $exists = $conn->fetchOne(
                        'SELECT COUNT(id) FROM employees WHERE emp_id = ?',
                        [$empId]
                    );

                    if (!$exists) {
                        // Insert
                        $fields = implode(', ', array_keys($params));
                        $placeholders = implode(', ', array_fill(0, count($params), '?'));

                        $sql = "INSERT INTO employees ($fields) VALUES ($placeholders)";
                        $conn->executeStatement($sql, array_values($params));

                        $stats['imported']++;
                    } else {
                        $sets = [];
                        $values = [];

                        foreach ($params as $field => $value) {
                            $sets[] = "$field = ?";
                            $values[] = $value;
                        }

                        $values[] = $empId;

                        $sql = "UPDATE employees SET " . implode(', ', $sets) . " WHERE emp_id = ?";
                        $conn->executeStatement($sql, $values);

                        $stats['updated']++;
                    }

                    $processedRows++;

                    if ($processedRows % 1000 === 0) {
                        file_put_contents($logPath, "Processed rows: $processedRows\n", FILE_APPEND);
                    }

                } catch (\Exception $rowException) {
                    $stats['failed']++;
                    $stats['errors'][] = [
                        'row' => $rowCount + 1,
                        'message' => $rowException->getMessage()
                    ];

                    file_put_contents($skippedRowsPath, sprintf(
                        "Row #%d: %s\nData: %s\n",
                        $rowCount,
                        $rowException->getMessage(),
                        json_encode($rowData)
                    ), FILE_APPEND);

                    if (count($stats['errors']) > 50) {
                        $stats['errors'] = array_slice($stats['errors'], 0, 50);
                        $stats['errors'][] = ['message' => 'Too many errors. Only the first 50 are shown.'];
                        break;
                    }
                }
            }

            $conn->commit();

            file_put_contents($logPath, "\nImport results:\n" . json_encode($stats, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        } catch (\Exception $e) {
            // Rollback transaction if critical err
            $conn->rollBack();

            file_put_contents($logPath, "Critical transaction error: " . $e->getMessage() . "\n", FILE_APPEND);

            throw $e;
        }

        return $stats;
    }

    /**
     * prepare data for sql request
     */
    private function prepareEmployeeData(array $data, array $columnMap): array
    {
        $params = [];

        $params['emp_id'] = $this->sanitizeValue($data[$columnMap['empId']]);

        $dateFields = [
            'dateOfBirth' => 'date_of_birth',
            'dateOfJoining' => 'date_of_joining'
        ];

        foreach ($dateFields as $csvField => $dbField) {
            if (isset($data[$columnMap[$csvField]])) {
                try {
                    $dateStr = preg_replace('/[^0-9\/\-\.]/', '', $data[$columnMap[$csvField]]);

                    $formats = [
                        'm/d/Y',   // 1/6/1967
                        'd/m/Y',   // 6/1/1967
                        'Y-m-d',   // 1967-01-06
                        'Y/m/d'    // 1967/01/06
                    ];

                    $date = null;
                    foreach ($formats as $format) {
                        $date = \DateTime::createFromFormat($format, $dateStr);
                        if ($date !== false) {
                            break;
                        }
                    }

                    if ($date === false) {
                        throw new \Exception("Failed to recognize date");
                    }

                    $params[$dbField] = $date->format('Y-m-d');
                } catch (\Exception $e) {
                    throw new \Exception("Invalid date format for $csvField: " . $data[$columnMap[$csvField]]);
                }
            }
        }

        if (isset($data[$columnMap['timeOfBirth']])) {
            try {
                $timeStr = $data[$columnMap['timeOfBirth']];
                $time = $this->parseTime($timeStr);
                $params['time_of_birth'] = $time->format('H:i:s');
            } catch (\Exception $e) {
                throw new \Exception("Invalid time format for birth: {$timeStr}");
            }
        }

        $fields = [
            'namePrefix' => 'name_prefix',
            'firstName' => 'first_name',
            'middleInitial' => 'middle_initial',
            'lastName' => 'last_name',
            'gender' => 'gender',
            'email' => 'email',
            'phoneNo' => 'phone_no',
            'placeName' => 'place_name',
            'county' => 'county',
            'city' => 'city',
            'zip' => 'zip',
            'region' => 'region',
            'userName' => 'user_name'
        ];

        foreach ($fields as $csvField => $dbField) {
            if (isset($data[$columnMap[$csvField]])) {
                $params[$dbField] = $this->sanitizeValue($data[$columnMap[$csvField]]);
            }
        }

        $numericFields = [
            'ageInYears' => 'age_in_years',
            'ageInCompany' => 'age_in_company'
        ];

        foreach ($numericFields as $csvField => $dbField) {
            if (isset($data[$columnMap[$csvField]])) {
                $numValue = preg_replace('/[^0-9\.]/', '', $data[$columnMap[$csvField]]);
                $params[$dbField] = (float) $numValue;
            }
        }

        return $params;
    }

    /**
     * parse
     */
    private function parseCsv(string $csvContent): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csvContent);
        rewind($handle);

        $headers = fgetcsv($handle, 0, ',');
        fclose($handle);

        // clean space
        $headers = array_map('trim', $headers);

        return $headers;
    }

    /**
     * generate CSV rows
     */
    private function parseCsvRows(string $csvContent, array $headers): \Generator
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csvContent);
        rewind($handle);

        fgetcsv($handle, 0, ',');

        $index = 0;
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $data = $this->sanitizeRowData($data);

            if (empty(array_filter($data))) {
                continue;
            }

            if (count($data) == count($headers)) {
                yield $index => array_combine($headers, $data);
            }
            $index++;
        }

        fclose($handle);
    }

    private function mapColumns(array $headers): array
    {
        $mapping = [
            'Emp ID' => 'empId',
            'Name Prefix' => 'namePrefix',
            'First Name' => 'firstName',
            'Middle Initial' => 'middleInitial',
            'Last Name' => 'lastName',
            'Gender' => 'gender',
            'E Mail' => 'email',
            'Date of Birth' => 'dateOfBirth',
            'Time of Birth' => 'timeOfBirth',
            'Age in Yrs.' => 'ageInYears',
            'Date of Joining' => 'dateOfJoining',
            'Age in Company (Years)' => 'ageInCompany',
            'Phone No. ' => 'phoneNo',
            'Place Name' => 'placeName',
            'County' => 'county',
            'City' => 'city',
            'Zip' => 'zip',
            'Region' => 'region',
            'User Name' => 'userName'
        ];

        $columnMap = [];

        foreach ($mapping as $csvHeader => $entityField) {
            $headerIndex = $this->findHeaderIndex($headers, $csvHeader);
            if ($headerIndex !== false) {
                $columnMap[$entityField] = $headers[$headerIndex];
            }
        }

        return $columnMap;
    }

    private function findHeaderIndex(array $headers, string $searchHeader): ?int
    {
        $searchHeader = trim($searchHeader);
        foreach ($headers as $index => $header) {
            if (trim($header) === $searchHeader) {
                return $index;
            }
        }
        return null;
    }

    /**
     * validate
     */
    private function validateRowData(array $rowData, array $columnMap): bool
    {
        $requiredFields = ['empId', 'firstName', 'lastName'];

        foreach ($requiredFields as $field) {
            if (!isset($rowData[$columnMap[$field]]) ||
                trim($rowData[$columnMap[$field]]) === '') {
                return false;
            }
        }

        return true;
    }

    private function sanitizeRowData(array $rowData): array
    {
        return array_map(function($value) {
            return $this->sanitizeValue($value);
        }, $rowData);
    }

    private function sanitizeValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value, "\x00..\x1F");

        $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);

        return $value === '' ? null : $value;
    }

    /**
     * Parse time for different type of date ( case we have a lot in our file)
     */
    private function parseTime(string $timeStr): \DateTime
    {
        $timeStr = preg_replace('/[^0-9:\s\w]/', '', $timeStr);

        $formats = [
            'h:i:s A',   // 10:30:00 AM
            'H:i:s',     // 22:30:00
            'h:i A',     // 10:30 PM
            'H:i',       // 22:30
            'h:i:s',     // 10:30:00 ( without AM/PM )
            'h:i:sA',    // 10:30:00AM ( together, case we have it in our file )
            'h:i A',     // 10:30 AM
        ];

        foreach ($formats as $format) {
            $time = \DateTime::createFromFormat($format, $timeStr);
            if ($time !== false) {
                return $time;
            }
        }

        // if not, trying in this way
        try {
            $cleanTimeStr = preg_replace('/[^0-9:\s\w]/', '', $timeStr);

            preg_match('/(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?\s*(AM|PM)?/i', $cleanTimeStr, $matches);

            if (!empty($matches)) {
                $hour = intval($matches[1]);
                $minute = intval($matches[2]);
                $second = isset($matches[3]) ? intval($matches[3]) : 0;
                $ampm = isset($matches[4]) ? strtoupper($matches[4]) : null;

                if ($ampm) {
                    if ($ampm === 'PM' && $hour < 12) {
                        $hour += 12;
                    }
                    if ($ampm === 'AM' && $hour === 12) {
                        $hour = 0;
                    }
                }

                $time = new \DateTime();
                $time->setTime($hour, $minute, $second);
                return $time;
            }

            // last try
            return new \DateTime($timeStr);
        } catch (\Exception $e) {
            throw new \Exception("Cannot recognize type of date: {$timeStr}");
        }
    }}