<?php

namespace App\Repository;

use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Employee>
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function save(Employee $employee, bool $flush = true): void
    {
        $this->getEntityManager()->persist($employee);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Employee $employee, bool $flush = true): void
    {
        $this->getEntityManager()->remove($employee);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEmpId(string $empId): ?Employee
    {
        return $this->findOneBy(['empId' => $empId]);
    }

    /**
     *
     * @param int $page number of page
     * @param int $limit number of entries per page
     * @return array
     */
    public function findAllPaginated(int $page = 1, int $limit = 20): array
    {
        $query = $this->createQueryBuilder('e')
                      ->orderBy('e.id', 'ASC')
                      ->setFirstResult(($page - 1) * $limit)
                      ->setMaxResults($limit)
                      ->getQuery();

        $paginator = new Paginator($query);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'total' => $paginator->count(),
            'page' => $page,
            'limit' => $limit
        ];
    }
}