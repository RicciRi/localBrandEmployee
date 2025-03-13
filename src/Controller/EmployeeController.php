<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Repository\EmployeeRepository;
use App\Service\CsvImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api', name: 'api_')]
class EmployeeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmployeeRepository $employeeRepository,
        private readonly SerializerInterface $serializer,
        private readonly CsvImportService $csvImportService
    ) {
    }

    #[Route('/employee', name: 'employee_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(100, $request->query->getInt('limit', 20)));

        $result = $this->employeeRepository->findAllPaginated($page, $limit);

        return $this->json($result, Response::HTTP_OK, [], ['groups' => 'employee:read']);
    }

    #[Route('/employee/{id}', name: 'employee_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $employee = $this->employeeRepository->findByEmpId($id);

        if (!$employee) {
            return $this->json(['message' => 'Cannot find Employee'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($employee, Response::HTTP_OK, [], ['groups' => 'employee:read']);
    }

    #[Route('/delete/employee/{id}', name: 'employee_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $employee = $this->employeeRepository->findByEmpId($id);

        if (!$employee) {
            return $this->json(['message' => 'Cannot find Employee'], Response::HTTP_NOT_FOUND);
        }

        $this->employeeRepository->remove($employee);

        return $this->json(['message' => 'Employee successfully deleted'], Response::HTTP_OK);
    }

    #[Route('/employee', name: 'employee_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $contentType = $request->headers->get('Content-Type');

        if ($contentType !== 'text/csv') {
            return $this->json(['message' => 'Incorrect type of file. Need - text/csv.'], Response::HTTP_BAD_REQUEST);
        }

        $csvContent = $request->getContent();

        try {
            $stats = $this->csvImportService->importEmployeesFromCsv($csvContent);

            return $this->json([
                                   'message' => 'Import success',
                                   'stats' => $stats
                               ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                                   'message' => 'Error to import CSV',
                                   'error' => $e->getMessage()
                               ], Response::HTTP_BAD_REQUEST);
        }
    }
}