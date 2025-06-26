<?php

namespace App\Tests\Manager;

use App\Entity\Customer;
use App\Entity\User;
use App\Enum\CustomerTypeEnum;
use App\Manager\CustomerManager;
use App\Repository\UserRepository;
use App\Service\Logger\SystemLoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerManagerTest extends TestCase
{
    private EntityManagerInterface $entityManagerMock;
    private UserPasswordHasherInterface $passwordHasherMock;
    private UserRepository $userRepositoryMock;
    private SystemLoggerInterface $loggerMock;
    private CustomerManager $customerManager;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->loggerMock = $this->createMock(SystemLoggerInterface::class);

        $this->customerManager = new CustomerManager(
            $this->entityManagerMock,
            $this->passwordHasherMock,
            $this->userRepositoryMock,
            $this->loggerMock
        );
    }

    public function testCreateIndividualCustomerSuccessfully(): void
    {
        // Arrange
        $email = 'test@example.com';
        $plainPassword = 'a-strong-password';
        $firstName = 'John';
        $lastName = 'Doe';
        $hashedPassword = 'hashed_password_string';

        $this->passwordHasherMock
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), $plainPassword)
            ->willReturn($hashedPassword);

        $this->entityManagerMock
            ->expects($this->exactly(2))
            ->method('persist');

        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush');

        $this->loggerMock
            ->expects($this->once())
            ->method('info')
            ->with($this->stringContains('New individual customer created.'));

        // Act
        $customer = $this->customerManager->createIndividualCustomer($email, $plainPassword, $firstName, $lastName);

        // Assert
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame($firstName, $customer->getFirstName());
        $this->assertSame($lastName, $customer->getLastName());
        $this->assertEquals(CustomerTypeEnum::INDIVIDUAL, $customer->getType());

        $user = $customer->getUserAccount();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($hashedPassword, $user->getPassword());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isVerified());
        $this->assertContains('ROLE_CUSTOMER', $user->getRoles());
    }
}