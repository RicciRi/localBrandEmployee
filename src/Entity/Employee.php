<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: "employees")]
#[ORM\UniqueConstraint(name: "UNIQ_EMP_ID", columns: ["emp_id"])]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["employee:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?string $empId = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(["employee:read"])]
    private ?string $namePrefix = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?string $firstName = null;

    #[ORM\Column(length: 1, nullable: true)]
    #[Groups(["employee:read"])]
    private ?string $middleInitial = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?string $lastName = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ["M", "F", "Male", "Female"])]
    #[Groups(["employee:read"])]
    private ?string $gender = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(["employee:read"])]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?\DateTimeInterface $timeOfBirth = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(["employee:read"])]
    private ?float $ageInYears = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?\DateTimeInterface $dateOfJoining = null;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups(["employee:read"])]
    private ?float $ageInCompany = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?string $phoneNo = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(["employee:read"])]
    private ?string $placeName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(["employee:read"])]
    private ?string $county = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(["employee:read"])]
    private ?string $city = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(["employee:read"])]
    private ?string $zip = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(["employee:read"])]
    private ?string $region = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(["employee:read"])]
    private ?string $userName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmpId(): ?string
    {
        return $this->empId;
    }

    public function setEmpId(string $empId): static
    {
        $this->empId = $empId;

        return $this;
    }

    public function getNamePrefix(): ?string
    {
        return $this->namePrefix;
    }

    public function setNamePrefix(?string $namePrefix): static
    {
        $this->namePrefix = $namePrefix;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getMiddleInitial(): ?string
    {
        return $this->middleInitial;
    }

    public function setMiddleInitial(?string $middleInitial): static
    {
        $this->middleInitial = $middleInitial;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(\DateTimeInterface $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getTimeOfBirth(): ?\DateTimeInterface
    {
        return $this->timeOfBirth;
    }

    public function setTimeOfBirth(\DateTimeInterface $timeOfBirth): static
    {
        $this->timeOfBirth = $timeOfBirth;

        return $this;
    }

    public function getAgeInYears(): ?float
    {
        return $this->ageInYears;
    }

    public function setAgeInYears(float $ageInYears): static
    {
        $this->ageInYears = $ageInYears;

        return $this;
    }

    public function getDateOfJoining(): ?\DateTimeInterface
    {
        return $this->dateOfJoining;
    }

    public function setDateOfJoining(\DateTimeInterface $dateOfJoining): static
    {
        $this->dateOfJoining = $dateOfJoining;

        return $this;
    }

    public function getAgeInCompany(): ?float
    {
        return $this->ageInCompany;
    }

    public function setAgeInCompany(float $ageInCompany): static
    {
        $this->ageInCompany = $ageInCompany;

        return $this;
    }

    public function getPhoneNo(): ?string
    {
        return $this->phoneNo;
    }

    public function setPhoneNo(string $phoneNo): static
    {
        $this->phoneNo = $phoneNo;

        return $this;
    }

    public function getPlaceName(): ?string
    {
        return $this->placeName;
    }

    public function setPlaceName(?string $placeName): static
    {
        $this->placeName = $placeName;

        return $this;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function setCounty(?string $county): static
    {
        $this->county = $county;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }
}