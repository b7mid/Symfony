<?php

namespace App\DataFixtures;

use App\Entity\Job;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $jobData = [
            ['title' => 'Senior Full Stack Symfony Developer', 'description' => 'Leading backend architecture and API integrations with Symfony 7 and Doctrine ORM.', 'company' => 'TechCorp Solutions', 'location' => 'Tunis, Tunisia (Remote)'],
            ['title' => 'Junior Frontend React Engineer', 'description' => 'Building responsive user interfaces with Bootstrap and Tailwind components.', 'company' => 'InnovateLab', 'location' => 'Sousse, Tunisia'],
            ['title' => 'DevOps & Cloud Specialist', 'description' => 'Managing AWS/Azure cloud infrastructure, CI/CD pipelines, and Docker containers.', 'company' => 'CloudScale Africa', 'location' => 'Remote'],
            ['title' => 'Cybersecurity Analyst', 'description' => 'Performing vulnerability assessments and penetration testing with professional security utilities.', 'company' => 'SecureNet Global', 'location' => 'Kasserine, Tunisia']
        ];

        foreach ($jobData as $data) {
            $job = new Job();
            $job->setTitle($data['title']);
            $job->setDescription($data['description']);
            $job->setCompany($data['company']);
            $job->setLocation($data['location']);
            $manager->persist($job);
        }

        $manager->flush();
    }
}