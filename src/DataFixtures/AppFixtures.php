<?php

namespace App\DataFixtures;

use App\Entity\Organization;
use App\Entity\OrganizationMember;
use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create users
        $admin = $this->createUser($manager, 'admin@coop.local', 'Admin', 'User', 'password');
        $alice = $this->createUser($manager, 'alice@coop.local', 'Alice', 'Meier', 'password');
        $bob = $this->createUser($manager, 'bob@coop.local', 'Bob', 'Schmidt', 'password');

        $manager->flush();

        // Create organization
        $org = new Organization();
        $org->setName('Demo Organisation');
        $org->setSlug('demo-organisation');
        $org->setDescription('Eine Demo-Organisation zum Ausprobieren aller Funktionen.');
        $org->setOwner($admin);
        $manager->persist($org);

        // Add members
        $adminMember = new OrganizationMember();
        $adminMember->setOrganization($org);
        $adminMember->setUser($admin);
        $adminMember->setRole(OrganizationMember::ROLE_ADMIN);
        $manager->persist($adminMember);

        $aliceMember = new OrganizationMember();
        $aliceMember->setOrganization($org);
        $aliceMember->setUser($alice);
        $aliceMember->setRole(OrganizationMember::ROLE_MEMBER);
        $manager->persist($aliceMember);

        $bobMember = new OrganizationMember();
        $bobMember->setOrganization($org);
        $bobMember->setUser($bob);
        $bobMember->setRole(OrganizationMember::ROLE_MEMBER);
        $manager->persist($bobMember);

        $manager->flush();

        // Create projects
        $project1 = new Project();
        $project1->setOrganization($org);
        $project1->setName('Website Relaunch');
        $project1->setSlug('website-relaunch');
        $project1->setDescription('Redesign und technische Erneuerung der Firmenwebsite.');
        $project1->setIsPublic(true);
        $project1->setCreatedBy($admin);
        $manager->persist($project1);

        $pm1 = new ProjectMember();
        $pm1->setProject($project1);
        $pm1->setUser($admin);
        $pm1->setRole(ProjectMember::ROLE_ADMIN);
        $manager->persist($pm1);

        $pm2 = new ProjectMember();
        $pm2->setProject($project1);
        $pm2->setUser($alice);
        $pm2->setRole(ProjectMember::ROLE_MEMBER);
        $manager->persist($pm2);

        $project2 = new Project();
        $project2->setOrganization($org);
        $project2->setName('Internes Reporting');
        $project2->setSlug('internes-reporting');
        $project2->setDescription('Privates Projekt fur interne Berichte und Analysen.');
        $project2->setIsPublic(false);
        $project2->setCreatedBy($alice);
        $manager->persist($project2);

        $pm3 = new ProjectMember();
        $pm3->setProject($project2);
        $pm3->setUser($alice);
        $pm3->setRole(ProjectMember::ROLE_ADMIN);
        $manager->persist($pm3);

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, string $email, string $first, string $last, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($first);
        $user->setLastName($last);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setIsVerified(true);
        $manager->persist($user);
        return $user;
    }
}
